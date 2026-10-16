<?php

namespace Tests\Feature;

use App\Address;
use App\Order;
use App\Product;
use App\StoreRecord;
use App\User;
use App\Vendor;
use App\PurchaseOrder;
use App\Services\BatchStock;
use App\Services\Commerce;
use App\Services\CustomerProfile;
use App\Services\OrderMail;
use App\Services\PaymentFlow;
use App\Services\ProductWriter;
use App\Services\ReturnFlow;
use App\Services\ReviewWriter;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TransactionFailureTest extends TestCase
{
    use RefreshDatabase;
    private $product;
    private $buyer;
    private $admin;

    protected function setUp() : void
    {
        parent::setUp();
        $this->seed(\StoreSeeder::class);
        $this->product = Product::create([
            'name' => 'Atomic product',
            'sku' => 'ATOMIC-1',
            'price' => 3000,
            'stock' => 0,
            'active' => true,
            'variants' => 'Default',
        ]);
        DB::transaction(function () {
            BatchStock::receive($this->product, 5, [
                'batch_number' => 'ATOMIC-OLD',
                'received_at' => today()->subDays(2),
                'unit_cost' => 1000,
            ]);
            BatchStock::receive($this->product, 5, [
                'batch_number' => 'ATOMIC-NEW',
                'received_at' => today()->subDay(),
                'unit_cost' => 1200,
            ]);
        });
        $this->buyer = User::create([
            'name' => 'Atomic Buyer',
            'email' => 'atomic@example.test',
            'password' => 'hash',
        ]);
        CustomerProfile::save($this->buyer, [
            'name' => 'Atomic Buyer',
            'email' => $this->buyer->email,
            'phone' => '9876543210',
            'address' => '12 Test Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'type' => 'Home',
        ]);
        $this->admin = User::where('email', 'admin@novacart.test')->firstOrFail();
        $this->actingAs($this->buyer);
        $this->withoutExceptionHandling();
    }

    private function fault($fragment, $binding = null)
    {
        $armed = true;
        DB::listen(function (QueryExecuted $q) use ($fragment, $binding, &$armed) {
            if ($armed && strpos(strtolower($q->sql), $fragment) !== false && ($binding === null || in_array($binding, $q->bindings, true))) {
                $armed = false;
                throw new RuntimeException('Injected midway failure');
            }
        });
    }

    private function expectFailure($callback)
    {
        try {
            $callback();
            $this->fail('Expected injected failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Injected midway failure', $e->getMessage());
        }
    }

    private function snapshot($tables)
    {
        $s = [];
        foreach ($tables as $t) {
            $s[$t] = json_encode(DB::table($t)->orderBy('id')->get());
        }
        return $s;
    }

    private function checkoutInput($method = 'Test card / UPI')
    {
        return [
            'key' => (string) Str::uuid(),
            'address_id' => Address::where('user_id', $this->buyer->id)->value('id'),
            'shipping_id' => StoreRecord::in('shipping_methods')->value('id'),
            'payment' => $method,
            'email' => $this->buyer->email,
        ];
    }

    private function order($method = 'Test card / UPI')
    {
        Commerce::add($this->product->fresh(), 2, 'Default');
        return Commerce::checkout($this->checkoutInput($method));
    }

    public function test_checkout_failure_restores_cart_stock_and_all_related_records_then_retry_is_idempotent()
    {
        Commerce::add($this->product, 2, 'Default');
        $input = $this->checkoutInput();
        $tables = [
            'orders',
            'order_items',
            'order_addresses',
            'payments',
            'shipments',
            'product_batches',
            'batch_allocations',
            'inventory',
            'notifications',
            'store_mail_outbox',
            'cart_items',
        ];
        $before = $this->snapshot($tables);
        $level = DB::transactionLevel();
        $this->fault('insert into "shipments"');
        $this->expectFailure(function () use ($input) {
            Commerce::checkout($input);
        });
        $this->assertSame($before, $this->snapshot($tables));
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame($level, DB::transactionLevel());
        $order = Commerce::checkout($input);
        $again = Commerce::checkout($input);
        $this->assertSame($order->id, $again->id);
        $this->assertSame(1, Order::count());
        $this->assertSame(8, $this->product->fresh()->stock);
    }

    public function test_cancellation_failure_rolls_back_restored_batches_and_allocations()
    {
        $order = $this->order();
        $tables = [
            'orders',
            'payments',
            'product_batches',
            'batch_allocations',
            'inventory',
            'notifications',
            'store_mail_outbox',
        ];
        $before = $this->snapshot($tables);
        $this->fault('insert into "inventory"');
        $this->expectFailure(function () use ($order) {
            Commerce::status($order, 'Cancelled');
        });
        $this->assertSame($before, $this->snapshot($tables));
        $this->assertSame(8, $this->product->fresh()->stock);
        Commerce::status($order->fresh(), 'Cancelled');
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_failed_payment_rolls_back_nested_cancellation_when_final_payment_update_fails()
    {
        $order = $this->order(PaymentFlow::SANDBOX);
        $tables = [
            'orders',
            'payments',
            'product_batches',
            'batch_allocations',
            'inventory',
            'notifications',
            'store_mail_outbox',
        ];
        $before = $this->snapshot($tables);
        $this->fault('update "payments"', 'Failed');
        $this->expectFailure(function () use ($order) {
            PaymentFlow::settle($order, 'Failed');
        });
        $this->assertSame($before, $this->snapshot($tables));
        $this->assertSame('Pending', DB::table('payments')->value('state'));
        $this->assertSame(8, $this->product->fresh()->stock);
    }

    public function test_return_inspection_failure_rolls_back_stock_and_status_then_retry_restocks_once()
    {
        $order = $this->order();
        $order->update([
            'status' => 'Delivered',
            'timeline' => [['status' => 'Delivered', 'date' => now()->toIso8601String()]],
        ]);
        $id = ReturnFlow::request($order, [
            'key' => (string) Str::uuid(),
            'quantities' => [$order->items->first()->id => 1],
            'reason' => 'Unopened item',
        ]);
        $this->actingAs($this->admin);
        ReturnFlow::action($id, 'approve');
        $line = DB::table('order_return_items')->where('return_id', $id)->first();
        $tables = [
            'orders',
            'order_returns',
            'order_return_items',
            'product_batches',
            'batch_allocations',
            'inventory',
            'notifications',
            'store_mail_outbox',
        ];
        $before = $this->snapshot($tables);
        $this->fault('insert into "activity_logs"', 'Return receive');
        $input = ['restock' => [$line->id => 1], 'notes' => 'Inspected usable'];
        $this->expectFailure(function () use ($id, $input) {
            ReturnFlow::action($id, 'receive', $input);
        });
        $this->assertSame($before, $this->snapshot($tables));
        $this->assertSame(8, $this->product->fresh()->stock);
        ReturnFlow::action($id, 'receive', $input);
        $this->assertSame(9, $this->product->fresh()->stock);
    }

    public function test_selected_inward_failure_rolls_back_receipt_batch_ledger_and_purchase_totals()
    {
        $this->actingAs($this->admin);
        $vendor = Vendor::create(['name' => 'Atomic supplier', 'active' => true]);
        $po = PurchaseOrder::create([
            'number' => 'ATOMIC-PO',
            'request_key' => (string) Str::uuid(),
            'vendor_id' => $vendor->id,
            'created_by' => $this->admin->id,
            'order_date' => today(),
        ]);
        $line = $po->items()->create([
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'sku' => $this->product->sku,
            'category_name' => 'Test',
            'subcategory_name' => 'Test',
            'existing_stock' => 10,
            'quantity' => 5,
        ]);
        $payload = [
            'number' => 'ATOMIC-IN',
            'vendor_id' => $vendor->id,
            'purchase_order_id' => $po->id,
            'supplier_invoice_no' => 'ATOMIC-BILL',
            'inward_date' => today()->toDateString(),
            'invoice_date' => today()->toDateString(),
            'warehouse' => 'Main',
            'received_by' => 'Admin',
            'received_items' => [
                $line->id => [
                    'selected' => 1,
                    'quantity' => 2,
                    'batch_number' => 'ATOMIC-RECEIPT',
                    'unit_cost' => 12,
                    'gst_percent' => 0,
                ],
            ],
        ];
        $tables = [
            'inwards',
            'inward_items',
            'product_batches',
            'inventory',
            'purchase_orders',
            'purchase_order_items',
        ];
        $before = $this->snapshot($tables);
        $this->fault('update "purchase_orders"');
        $this->expectFailure(function () use ($payload) {
            $this->post('/admin/inward', $payload);
        });
        $this->assertSame($before, $this->snapshot($tables));
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->post('/admin/inward', $payload)->assertSessionHasNoErrors();
        $this->assertSame(12, $this->product->fresh()->stock);
    }

    public function test_outward_confirmation_failure_keeps_order_and_reserved_stock_unchanged()
    {
        $order = $this->order();
        $this->actingAs($this->admin);
        $tables = [
            'orders',
            'outwards',
            'outward_items',
            'inventory',
            'batch_allocations',
        ];
        $before = $this->snapshot($tables);
        $this->fault('insert into "activity_logs"', 'Outward Pending');
        $this->expectFailure(function () use ($order) {
            $this->post('/admin/outward', [
                'request_key' => (string) Str::uuid(),
                'type' => 'Order Dispatch',
                'outward_date' => today()->toDateString(),
                'source' => 'Main',
                'order_id' => $order->id,
                'intent' => 'confirm',
            ]);
        });
        $this->assertSame($before, $this->snapshot($tables));
        $this->assertSame(8, $this->product->fresh()->stock);
    }

    public function test_admin_role_sync_failure_does_not_save_half_a_user_update()
    {
        $this->actingAs($this->admin);
        $before = $this->buyer->fresh()->toArray();
        $roles = DB::table('role_user')->get()->toArray();
        $this->fault('insert into "role_user"');
        $this->expectFailure(function () {
            $this->put('/admin/users/' . $this->buyer->id, [
                'name' => 'Changed Buyer',
                'email' => 'changed@example.test',
                'role_id' => StoreRecord::in('roles')->where('name', 'customer')->value('id'),
                'active' => 1,
            ]);
        });
        $this->assertSame($before, $this->buyer->fresh()->toArray());
        $this->assertEquals($roles, DB::table('role_user')->get()->toArray());
    }

    public function test_product_failure_removes_new_upload_and_rolls_back_stock_gallery_and_audit()
    {
        Storage::fake('public');
        $this->actingAs($this->admin);
        $this->product->images()->create(['url' => '/storage/original.jpg', 'position' => 0]);
        $tables = [
            'products',
            'product_images',
            'product_batches',
            'inventory',
            'activity_logs',
        ];
        $before = $this->snapshot($tables);
        $this->fault('insert into "activity_logs"', 'Saved products');
        $this->expectFailure(function () {
            ProductWriter::save($this->product, [
                'name' => 'Changed',
                'sku' => 'ATOMIC-1',
                'price' => 9000,
                'stock' => 13,
                'active' => true,
            ], '/storage/new.jpg', UploadedFile::fake()->image('new.jpg'));
        });
        $this->assertSame($before, $this->snapshot($tables));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_review_failure_removes_new_upload_and_leaves_no_partial_review()
    {
        Storage::fake('public');
        $this->fault('insert into "reviews"');
        $this->expectFailure(function () {
            ReviewWriter::save($this->product, null, [
                'rating' => 5,
                'title' => 'Nice',
                'text' => 'Good product',
            ], UploadedFile::fake()->image('review.jpg'));
        });
        $this->assertSame(0, DB::table('reviews')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_mail_is_sent_outside_worker_transaction_and_success_is_not_repeated()
    {
        $this->order();
        config(['mail.default' => 'array']);
        $base = DB::transactionLevel();
        Mail::shouldReceive('raw')->once()->andReturnUsing(function () use ($base) {
            $this->assertSame($base, DB::transactionLevel());
        });
        $this->assertSame(1, OrderMail::process());
        $this->assertSame('Logged', DB::table('store_mail_outbox')->value('status'));
        $this->assertSame(0, OrderMail::process());
    }

    public function test_failed_mail_records_retry_without_rolling_back_the_order()
    {
        $this->order();
        config(['mail.default' => 'array']);
        Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('Transport unavailable'));
        $this->assertSame(0, OrderMail::process());
        $row = DB::table('store_mail_outbox')->first();
        $this->assertSame('Pending', $row->status);
        $this->assertEquals(1, $row->attempts);
        $this->assertNotNull($row->retry_at);
        $this->assertSame(1, Order::count());
        $this->assertSame(8, $this->product->fresh()->stock);
    }

    public function test_sent_mail_ack_failure_is_not_automatically_resent()
    {
        $this->order();
        config(['mail.default' => 'array']);
        Mail::shouldReceive('raw')->once();
        $this->fault('update "store_mail_outbox"', 'Logged');
        $this->expectFailure(function () {
            OrderMail::process();
        });
        $this->assertSame('Sending', DB::table('store_mail_outbox')->value('status'));
        DB::table('store_mail_outbox')->update(['retry_at' => now()->subMinute()]);
        $this->assertSame(0, OrderMail::process());
        $this->assertSame('Uncertain', DB::table('store_mail_outbox')->value('status'));
    }

    public function test_registration_keeps_created_account_and_reports_verification_delivery_failure_truthfully()
    {
        auth()->logout();
        \Illuminate\Support\Facades\Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Mail unavailable'));
        $response = $this->post('/register', [
            'name' => 'Notification Buyer',
            'email' => 'notify-buyer@example.test',
            'phone' => '9876543211',
            'address' => '12 Test Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'type' => 'Home',
            'password' => 'BuyerPass123!',
            'password_confirmation' => 'BuyerPass123!',
        ]);
        $response->assertRedirect('/account')->assertSessionHas('success', function ($message) {
            return strpos($message, 'could not be sent') !== false;
        });
        $user = User::where('email', 'notify-buyer@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(Address::where('user_id', $user->id)->first());
    }

    public function test_external_mail_worker_refuses_to_send_inside_a_callers_transaction()
    {
        $this->order();
        config(['mail.default' => 'smtp']);
        Mail::shouldReceive('raw')->never();
        DB::beginTransaction();
        try {
            try {
                OrderMail::process();
                $this->fail('Expected transaction guard');
            } catch (\LogicException $e) {
                $this->assertSame('Send external mail outside the caller transaction.', $e->getMessage());
            }
            $this->assertSame('Pending', DB::table('store_mail_outbox')->value('status'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_expense_and_audit_are_rolled_back_together()
    {
        $this->actingAs($this->admin);
        $before = $this->snapshot(['store_expenses', 'activity_logs']);
        $this->fault('insert into "activity_logs"', 'Operating expense recorded');
        $values = [
            'spent_on' => today()->toDateString(),
            'description' => 'Atomic packaging',
            'amount' => 50,
        ];
        $this->expectFailure(function () use ($values) {
            $this->post('/admin/store-operations/expenses', $values);
        });
        $this->assertSame($before, $this->snapshot(['store_expenses', 'activity_logs']));
        $this->post('/admin/store-operations/expenses', $values)->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('store_expenses')->count());
    }

    public function test_password_reset_token_delete_failure_preserves_old_password_and_token()
    {
        $this->buyer->password = \Illuminate\Support\Facades\Hash::make('OriginalPass123');
        $this->buyer->save();
        $token = \Illuminate\Support\Facades\Password::broker()->createToken($this->buyer);
        $before = $this->snapshot(['users', 'password_resets']);
        $this->fault('delete from "password_resets"');
        $values = [
            'email' => $this->buyer->email,
            'token' => $token,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ];
        $this->expectFailure(function () use ($values) {
            $this->post('/password/reset', $values);
        });
        $this->assertSame($before, $this->snapshot(['users', 'password_resets']));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('OriginalPass123', $this->buyer->fresh()->password));
        $this->post('/password/reset', $values)->assertRedirect('/login')->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('password_resets')->count());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword456', $this->buyer->fresh()->password));
        $password = $this->buyer->fresh()->password;
        $this->post('/password/reset', $values)->assertSessionHasErrors('email');
        $this->assertSame($password, $this->buyer->fresh()->password);
    }

    public function test_forgot_password_mail_failure_does_not_claim_delivery_or_change_password()
    {
        auth()->logout();
        $password = $this->buyer->fresh()->password;
        \Illuminate\Support\Facades\Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Mail unavailable'));
        $this->post('/password/email', ['email' => $this->buyer->email])->assertSessionHasErrors('email')->assertSessionMissing('success');
        $this->assertSame($password, $this->buyer->fresh()->password);
    }

    private function vendorRequest($uri, $values, $vendor, $files = [])
    {
        $request = \Illuminate\Http\Request::create($uri, 'POST', $values, [], $files);
        $request->setUserResolver(function () {
            return auth()->user();
        });
        $request->attributes->set('vendor', $vendor);
        return $request;
    }

    public function test_legacy_vendor_settings_failure_rolls_back_user_and_store_together()
    {
        $vendor = Vendor::create([
            'name' => 'Atomic legacy store',
            'active' => true,
            'user_id' => $this->buyer->id,
            'data' => ['notify_orders' => false],
        ]);
        $before = $this->snapshot([
            'users',
            'vendors',
            'activity_logs',
        ]);
        $request = $this->vendorRequest('/vendor/settings', [
            'name' => 'Changed vendor',
            'email' => 'changed-vendor@example.test',
            'notify_orders' => true,
        ], $vendor);
        $this->fault('update "vendors"');
        $this->expectFailure(function () use ($request) {
            (new \App\Http\Controllers\VendorController())->settingsSave($request);
        });
        $this->assertSame($before, $this->snapshot([
            'users',
            'vendors',
            'activity_logs',
        ]));
        $this->assertFalse((bool) config('store_management.vendor_portal'));
    }

    public function test_legacy_vendor_product_failure_removes_upload_and_partial_product()
    {
        Storage::fake('public');
        $vendor = Vendor::create([
            'name' => 'Atomic legacy store',
            'active' => true,
            'user_id' => $this->buyer->id,
        ]);
        $request = $this->vendorRequest('/vendor/products', [
            'name' => 'Vendor atomic product',
            'sku' => 'VENDOR-ATOMIC-NEW',
            'category_id' => Product::first()->category_id,
            'price' => 10,
            'variants' => 'Default',
            'low_stock_threshold' => 5,
            'delivery_type' => 'standard',
            'delivery_minutes' => 60,
            'active' => 1,
        ], $vendor, ['image_upload' => UploadedFile::fake()->image('vendor-product.jpg')]);
        $before = $this->snapshot(['products', 'activity_logs']);
        $this->fault('insert into "activity_logs"', 'Vendor product saved');
        $this->expectFailure(function () use ($request) {
            (new \App\Http\Controllers\VendorController())->productSave($request);
        });
        $this->assertSame($before, $this->snapshot(['products', 'activity_logs']));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_legacy_vendor_logo_failure_preserves_profile_and_cleans_new_file()
    {
        Storage::fake('public');
        $vendor = Vendor::create([
            'name' => 'Original store',
            'active' => true,
            'user_id' => $this->buyer->id,
            'data' => ['logo' => '/storage/existing.png'],
        ]);
        $before = $this->snapshot(['vendors', 'activity_logs']);
        $request = $this->vendorRequest('/vendor/profile', ['name' => 'Changed store', 'email' => 'store@example.test'], $vendor, ['logo' => UploadedFile::fake()->image('logo.jpg')]);
        $this->fault('insert into "activity_logs"', 'Vendor profile updated');
        $this->expectFailure(function () use ($request) {
            (new \App\Http\Controllers\VendorController())->profileSave($request);
        });
        $this->assertSame($before, $this->snapshot(['vendors', 'activity_logs']));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_legacy_vendor_announcement_failure_does_not_leave_partial_recipients()
    {
        config(['store_management.vendor_portal' => true]);
        $this->actingAs($this->admin);
        Vendor::create([
            'name' => 'First recipient',
            'active' => true,
            'user_id' => $this->buyer->id,
        ]);
        Vendor::create([
            'name' => 'Second recipient',
            'active' => true,
            'user_id' => $this->admin->id,
        ]);
        $before = $this->snapshot(['notifications', 'activity_logs']);
        $count = 0;
        DB::listen(function (QueryExecuted $query) use (&$count) {
            if (strpos(strtolower($query->sql), 'insert into "notifications"') !== false && ++$count === 2) {
                throw new RuntimeException('Injected midway failure');
            }
        });
        $request = \Illuminate\Http\Request::create('/admin/vendor/announce', 'POST', ['message' => 'Atomic announcement']);
        $this->expectFailure(function () use ($request) {
            (new \App\Http\Controllers\AdminVendorController())->announce($request);
        });
        $this->assertSame($before, $this->snapshot(['notifications', 'activity_logs']));
    }

    public function test_rider_zone_replacement_failure_preserves_previous_assignments()
    {
        $this->actingAs($this->admin);
        $rider = User::create([
            'name' => 'Atomic rider',
            'email' => 'atomic-rider@example.test',
            'password' => 'hash',
        ]);
        $rider->role_id = StoreRecord::in('roles')->where('name', 'delivery partner')->value('id');
        $rider->save();
        $ids = [];
        foreach (['Old', 'New'] as $label) {
            $ids[] = DB::table('delivery_zones')->insertGetId([
                'name' => 'Atomic ' . $label,
                'city' => 'Mumbai',
                'pincodes' => '400001',
                'opens_at' => '08:00',
                'closes_at' => '22:00',
                'travel_minutes' => 30,
                'active' => true,
            ]);
        }
        DB::table('delivery_zone_riders')->insert(['rider_id' => $rider->id, 'zone_id' => $ids[0]]);
        $before = $this->snapshot(['delivery_zone_riders']);
        $this->fault('insert into "delivery_zone_riders"');
        $this->expectFailure(function () use ($rider, $ids) {
            $this->post('/admin/delivery/riders/' . $rider->id . '/zones', ['zone_ids' => [$ids[1]]]);
        });
        $this->assertSame($before, $this->snapshot(['delivery_zone_riders']));
    }
}
