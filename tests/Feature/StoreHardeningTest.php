<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Product;
use App\Order;
use App\User;
use App\Address;
use App\StoreRecord;
use App\Services\BatchStock;
use App\Services\CustomerProfile;
use App\Services\PaymentFlow;
use App\Services\StoreReporting;
class StoreHardeningTest extends TestCase {
 use RefreshDatabase;
 private $product;private $buyer;
 protected function setUp():void{parent::setUp();$this->seed(\StoreSeeder::class);$this->product=Product::create(['name'=>'Costed item','sku'=>'HARDEN-1','price'=>3000,'stock'=>0,'active'=>true,'variants'=>'Default','category_id'=>Product::first()->category_id]);DB::transaction(function(){BatchStock::receive($this->product,10,['batch_number'=>'SAFE-101','received_at'=>today()->toDateString(),'unit_cost'=>1000]);});$this->buyer=User::create(['name'=>'Test buyer','email'=>'harden@example.test','password'=>bcrypt('BuyerPass123!')]);CustomerProfile::save($this->buyer,['name'=>'Test buyer','email'=>$this->buyer->email,'phone'=>'9876543210','address'=>'12 Test Road','city'=>'Mumbai','state'=>'Maharashtra','pincode'=>'400001','type'=>'Home']);$this->actingAs($this->buyer);}
 private function order($method=PaymentFlow::SANDBOX){$this->post('/cart/add/'.$this->product->id,['quantity'=>2,'variant'=>'Default'])->assertSessionHasNoErrors();$this->post('/checkout',['key'=>(string)Str::uuid(),'address_id'=>Address::where('user_id',$this->buyer->id)->value('id'),'shipping_id'=>StoreRecord::in('shipping_methods')->value('id'),'payment'=>$method,'email'=>$this->buyer->email])->assertSessionHasNoErrors();return Order::latest('id')->firstOrFail();}
 public function test_payment_success_is_once_only_and_pending_orders_cannot_dispatch(){
  $order=$this->order();$this->assertEquals(8,$this->product->fresh()->stock);$this->assertEquals('Pending',DB::table('payments')->value('state'));$this->actingAs(User::where('email','admin@novacart.test')->first());$this->post('/admin/order-workspace/'.$order->id.'/prepare',['request_key'=>(string)Str::uuid()])->assertSessionHasErrors('store');$this->assertEquals(0,\App\Outward::count());$this->actingAs($this->buyer);$this->post('/orders/'.$order->id.'/sandbox-payment',['outcome'=>'Paid'])->assertSessionHasNoErrors();$this->post('/orders/'.$order->id.'/sandbox-payment',['outcome'=>'Paid'])->assertSessionHasNoErrors();$this->assertEquals(8,$this->product->fresh()->stock);$this->assertEquals(1,DB::table('batch_allocations')->count());$this->dispatchTestOrder($order);$this->get('/admin/order-workspace/'.$order->id)->assertOk()->assertSee('SAFE-101')->assertSee('Paid');
 }
 public function test_failed_payment_releases_original_stock_once_and_cannot_later_succeed(){
  $order=$this->order();$this->post('/orders/'.$order->id.'/sandbox-payment',['outcome'=>'Failed'])->assertSessionHasNoErrors();$this->post('/orders/'.$order->id.'/sandbox-payment',['outcome'=>'Failed'])->assertSessionHasNoErrors();$this->post('/orders/'.$order->id.'/sandbox-payment',['outcome'=>'Paid'])->assertSessionHasErrors('store');$this->assertEquals('Cancelled',$order->fresh()->status);$this->assertEquals(10,$this->product->fresh()->stock);$this->assertEquals(10,DB::table('product_batches')->where('product_id',$this->product->id)->sum('remaining_quantity'));
 }
 public function test_timeout_and_customer_cancel_resolve_pending_reservations(){
  $order=$this->order();DB::table('payments')->where('order_id',$order->id)->update(['expires_at'=>now()->subMinute()]);$this->artisan('store:expire-payments')->assertExitCode(0);$this->artisan('store:expire-payments')->assertExitCode(0);$this->assertEquals('Expired',DB::table('payments')->value('state'));$this->assertEquals(10,$this->product->fresh()->stock);$next=$this->order();$this->post('/orders/'.$next->id.'/status',['status'=>'Cancelled'])->assertSessionHasNoErrors();$this->assertEquals('Cancelled',DB::table('payments')->where('order_id',$next->id)->value('state'));$this->assertEquals(10,$this->product->fresh()->stock);
 }
 public function test_other_customers_cannot_confirm_payment_and_sandbox_is_disabled_in_production(){
  $order=$this->order();$other=User::create(['name'=>'Other','email'=>'other@harden.test','password'=>'hash']);$this->actingAs($other)->post('/orders/'.$order->id.'/sandbox-payment',['outcome'=>'Paid'])->assertForbidden();$this->actingAs($this->buyer);config(['store_management.payment_sandbox'=>false]);$this->post('/orders/'.$order->id.'/sandbox-payment',['outcome'=>'Paid'])->assertNotFound();$this->assertEquals('Pending',DB::table('payments')->value('state'));
 }
 public function test_invalid_order_status_skips_are_rejected(){
  $order=$this->order('Test card / UPI');$this->actingAs(User::where('email','admin@novacart.test')->first());foreach(['Delivered','Shipped','Picked up','On the way'] as $status)$this->post('/admin/orders/'.$order->id.'/status',['status'=>$status])->assertSessionHasErrors('store');$this->assertEquals('Placed',$order->fresh()->status);$this->dispatchTestOrder($order,true);$this->post('/admin/orders/'.$order->id.'/status',['status'=>'Confirmed'])->assertSessionHasErrors('store');
 }
 public function test_perishables_with_missing_expiry_are_unsellable_without_changing_recorded_stock(){
  $this->product->update(['delivery_type'=>'fresh']);$this->assertEquals(0,BatchStock::available($this->product));$this->assertEquals(10,$this->product->fresh()->stock);$this->post('/cart/add/'.$this->product->id,['quantity'=>1,'variant'=>'Default'])->assertSessionHasErrors('store');DB::table('product_batches')->where('product_id',$this->product->id)->update(['expires_on'=>today()->addDays(3)->toDateString()]);$this->assertEquals(10,BatchStock::available($this->product));
 }
 public function test_costs_and_expenses_use_fifo_and_unknown_cost_is_not_zero(){
  $order=$this->order('Test card / UPI');$this->dispatchTestOrder($order,true);$from=today()->toDateString();$report=StoreReporting::calculate($from,$from);$this->assertEquals(6000,$report['merchandise']);$this->assertEquals(2000,$report['known_cost']);$this->assertEquals(4000,$report['margin']);$this->post('/admin/store-operations/expenses',['spent_on'=>$from,'description'=>'Packaging','amount'=>5])->assertSessionHasNoErrors();$this->assertEquals(3500,StoreReporting::calculate($from,$from)['result']);DB::table('batch_allocations')->where('order_item_id',$order->items->first()->id)->update(['unit_cost'=>null]);$this->assertNull(StoreReporting::calculate($from,$from)['margin']);$this->get('/admin/store-operations')->assertOk()->assertSee('Incomplete costs');
 }
 public function test_mail_queue_logs_locally_and_does_not_reprocess_successful_messages(){
  $this->order('Test card / UPI');config(['mail.default'=>'array']);$this->assertEquals(1,DB::table('store_mail_outbox')->where('status','Pending')->count());$this->artisan('store:send-mail')->assertExitCode(0);$this->assertEquals('Logged',DB::table('store_mail_outbox')->value('status'));$this->assertEquals(0,\App\Services\OrderMail::process());
 }
 public function test_shopping_intent_requires_confirmation_and_revalidates_availability(){
  auth()->logout();$this->postJson('/shopping/intent',['product_id'=>$this->product->id,'kind'=>'cart','quantity'=>2,'variant'=>'Default'])->assertOk();$this->get('/shopping/resume')->assertRedirect('/login');$this->actingAs($this->buyer);$this->get('/shopping/resume')->assertOk()->assertSee('Continue where you left off');$this->assertEquals(0,DB::table('cart_items')->count());$this->post('/shopping/resume')->assertRedirect('/cart');$this->post('/shopping/resume')->assertRedirect('/shop');$this->assertEquals(2,DB::table('cart_items')->value('quantity'));
 }
 public function test_available_filter_matches_perishable_expiry_and_validates_price_range(){
  $this->product->update(['delivery_type'=>'fresh']);$this->assertEquals(0,$this->get('/shop?stock=1&q=HARDEN-1')->assertOk()->viewData('products')->total());DB::table('product_batches')->where('product_id',$this->product->id)->update(['expires_on'=>today()->addDays(3)->toDateString()]);$response=$this->get('/shop?stock=1&q=HARDEN-1')->assertOk();$this->assertEquals(1,$response->viewData('products')->total(),json_encode(['p'=>$this->product->fresh()->toArray(),'b'=>DB::table('product_batches')->where('product_id',$this->product->id)->get()->toArray()]));$this->get('/shop?min=100&max=5')->assertSessionHasErrors('store');
 }
 public function test_rider_must_supply_proof_and_failed_attempt_does_not_complete_order(){
  $order=$this->order('Test card / UPI');$this->dispatchTestOrder($order);$rider=User::create(['name'=>'Rider','email'=>'proof-rider@example.test','password'=>'hash']);$rider->role_id=StoreRecord::in('roles')->where('name','delivery partner')->value('id');$rider->save();$zone=DB::table('delivery_zones')->insertGetId(['name'=>'Test zone','pincodes'=>'400001','opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'active'=>true,'express'=>true,'created_at'=>now(),'updated_at'=>now()]);DB::table('deliveries')->insert(['order_id'=>$order->id,'zone_id'=>$zone,'rider_id'=>$rider->id,'promised_from'=>now(),'promised_to'=>now()->addHour(),'created_at'=>now(),'updated_at'=>now()]);$this->actingAs($rider);$this->postJson('/rider/orders/'.$order->id,['status'=>'Picked up'])->assertOk();$this->postJson('/rider/orders/'.$order->id,['status'=>'On the way'])->assertOk();$this->postJson('/rider/orders/'.$order->id,['failure_reason'=>'Customer was unavailable'])->assertOk();$this->assertEquals('On the way',$order->fresh()->status);$this->postJson('/rider/orders/'.$order->id,['status'=>'Delivered'])->assertStatus(422);$this->postJson('/rider/orders/'.$order->id,['status'=>'Delivered','proof'=>'Received by the customer at the door'])->assertOk();$this->assertNotNull(DB::table('deliveries')->value('proof'));$this->assertNull(DB::table('deliveries')->value('failure_reason'));
 }
 public function test_inspected_return_reverses_merchandise_and_cost_before_cash_refund(){
  $order=$this->order('Test card / UPI');$this->dispatchTestOrder($order,true);$this->actingAs($this->buyer);$this->post('/orders/'.$order->id.'/returns',['key'=>(string)Str::uuid(),'reason'=>'Returning one unopened pack','quantities'=>[$order->items->first()->id=>1]])->assertSessionHasNoErrors();$return=DB::table('order_returns')->first();$line=DB::table('order_return_items')->first();$this->actingAs(User::where('email','admin@novacart.test')->first());$this->post('/admin/returns/'.$return->id.'/action',['action'=>'approve'])->assertSessionHasNoErrors();$this->post('/admin/returns/'.$return->id.'/action',['action'=>'receive','notes'=>'Inspected and usable','restock'=>[$line->id=>1]])->assertSessionHasNoErrors();$report=StoreReporting::calculate(today()->toDateString(),today()->toDateString());$this->assertEquals(3000,$report['merchandise_refunds']);$this->assertEquals(1000,$report['known_cost']);$this->assertEquals(2000,$report['margin']);$this->assertEquals('Pending',DB::table('order_returns')->value('refund_status'));
 }
 public function test_cancelled_outward_can_be_reprepared_without_restoring_or_deducting_order_stock(){
  $order=$this->order('Test card / UPI');$this->actingAs(User::where('email','admin@novacart.test')->first());$this->post('/admin/order-workspace/'.$order->id.'/prepare',['request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();$out=\App\Outward::first();$this->post('/admin/outward/'.$out->id.'/action',['action'=>'cancel'])->assertSessionHasNoErrors();$this->get('/admin/order-workspace/'.$order->id)->assertOk()->assertSee('Prepare order outward');$this->post('/admin/order-workspace/'.$order->id.'/prepare',['request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();$this->assertEquals(8,$this->product->fresh()->stock);$this->assertEquals(2,\App\Outward::count());$this->assertEquals(1,\App\Outward::where('active_order_id',$order->id)->count());
 }
}
