<?php
namespace Tests\Feature;

use App\User;
use App\Order;
use App\Product;
use App\StoreRecord;
use App\Services\VendorFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreOwnerDashboardTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp():void { parent::setUp();$this->seed(\StoreSeeder::class);$this->actingAs(User::where('email','admin@novacart.test')->first()); }
 public function test_store_team_modules_and_dashboard_are_connected(){
  foreach(['/admin','/admin/products','/admin/inventory','/admin/categories','/admin/vendors','/admin/orders','/admin/outward','/admin/inward','/admin/batches','/admin/reports','/admin/payments','/admin/reviews','/admin/settings','/admin/returns','/admin/team-profile'] as $url)$this->get($url)->assertOk();
  $this->get('/admin')->assertSee('Store performance')->assertSee('Your store workflow')->assertSee('Expired stock')->assertSee('Vendors')->assertDontSee('Vendor stock requests');
  $this->get('/admin/products/create')->assertOk()->assertDontSee('name="vendor_id"',false);
  $this->get('/admin/vendors/create')->assertOk()->assertDontSee('name="user_id"',false)->assertDontSee('name="commission_percent"',false);
 }
 public function test_vendor_login_is_retired_and_cannot_be_used_to_mutate_stock(){
  $stock=Product::sum('stock');auth()->logout();$this->get('/vendor/login')->assertRedirect('/admin/login');$this->get('/vendor/products')->assertRedirect('/admin/login');$this->post('/vendor/inventory/requests',['quantity'=>999])->assertForbidden();$this->assertEquals($stock,Product::sum('stock'));
 }
 public function test_dates_and_financial_permissions_are_enforced(){
  $this->get('/admin?from=2026-10-20&to=2026-10-01')->assertSessionHasErrors('to');
  $this->get('/admin?from=2026-01-01&to=2026-12-31')->assertOk()->assertViewHas('from','2026-01-01');
  $role=StoreRecord::in('roles')->create(['name'=>'Store catalogue assistant','data'=>['permissions'=>['dashboard','products']]]);$user=User::create(['name'=>'Assistant','email'=>'assistant@example.test','password'=>'hash']);$user->role_id=$role->id;$user->save();$this->actingAs($user);$this->get('/admin')->assertOk()->assertDontSee('Store performance')->assertDontSee('Prepare customer orders');$this->get('/admin/reports')->assertForbidden();
 }
 public function test_profile_cannot_change_roles_and_rejects_duplicate_email(){
  $user=auth()->user();$role=$user->role_id;$other=User::create(['name'=>'Customer','email'=>'customer@example.test','password'=>'hash']);
  $this->post('/admin/team-profile',['name'=>'Team admin','email'=>$other->email])->assertSessionHasErrors('email');
  $this->post('/admin/team-profile',['name'=>'Team admin','email'=>'teamadmin@example.test','role_id'=>999,'active'=>0])->assertSessionHasNoErrors();$this->assertEquals($role,$user->fresh()->role_id);$this->assertTrue($user->fresh()->active);$this->assertEquals('teamadmin@example.test',$user->fresh()->email);
  $this->actingAs($other);$this->get('/admin/team-profile')->assertForbidden();
 }
 public function test_store_orders_do_not_create_marketplace_commission_or_vendor_tasks(){
  $order=Order::create(['number'=>'OWNER-TEST','invoice_number'=>'OWNER-INV','idempotency_key'=>'owner-test','session_key'=>'owner-test','shipping_method_id'=>1,'timeline'=>[],'email'=>'buyer@example.test','subtotal'=>1000,'total'=>1000,'discount'=>0,'shipping'=>0,'tax'=>0,'status'=>'Placed','payment_method'=>'Test']);
  VendorFlow::captureOrder($order);$this->assertEquals(0,DB::table('vendor_fulfilments')->count());
  $this->get('/admin?from='.today()->toDateString().'&to='.today()->toDateString())->assertOk()->assertViewHas('metrics',function($metrics){return $metrics[0][1]===1000||$metrics[0][1]=='1000';});
 }
}
