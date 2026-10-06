<?php
namespace Tests\Audit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use App\Product;
use App\Address;
use App\Order;
use App\StoreRecord;
use App\Services\CustomerProfile;
use App\Services\Commerce;
use App\Services\Delivery;
use Carbon\Carbon;
/** Diagnostic probes assert the observed faulty behavior, not the desired behavior. */
class ProjectAuditTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(\StoreSeeder::class);Carbon::setTestNow('2026-10-06 10:00:00');}
 protected function tearDown():void{Carbon::setTestNow();parent::tearDown();}
 private function buyer(){$u=User::create(['name'=>'Audit Buyer','email'=>'audit@example.test','password'=>bcrypt('BuyerPass123!')]);$u->role_id=StoreRecord::in('roles')->where('name','customer')->value('id');$u->save();CustomerProfile::save($u,['name'=>$u->name,'email'=>$u->email,'phone'=>'9876543210','address'=>'12 Audit Street','city'=>'Mumbai','state'=>'Maharashtra','pincode'=>'400001','type'=>'Home']);$this->actingAs($u);return $u;}
 private function place(){return $this->post('/checkout',['key'=>(string)Str::uuid(),'address_id'=>Address::first()->id,'shipping_id'=>StoreRecord::in('shipping_methods')->first()->id,'email'=>'audit@example.test','payment'=>'Test card / UPI']);}
 private function admin(){$this->actingAs(User::where('email','admin@novacart.test')->first());}
 public function test_confirm_default_address_edit_removes_default_flag(){
  $u=$this->buyer();$a=Address::first();$data=$a->only(['name','phone','address','city','state','pincode','type']);$data['address']='14 Updated Street';$data['is_default']=1;$this->put('/addresses/'.$a->id,$data)->assertSessionHasNoErrors();$this->assertFalse($a->fresh()->is_default);$this->assertFalse(CustomerProfile::complete($u->fresh()));
 }
 public function test_confirm_removed_variant_still_can_be_checked_out(){
  $this->buyer();$p=Product::first();$original=$p->options[0];$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$original])->assertSessionHasNoErrors();$p->update(['variants'=>'Replacement']);$this->place()->assertSessionHasNoErrors();$this->assertEquals($original,Order::first()->items->first()->variant);
 }
 public function test_confirm_delivered_order_can_be_reopened_and_cancelled(){
  $this->buyer();$p=Product::first();$before=$p->stock;$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]]);$this->place();$o=Order::first();$this->admin();foreach(['Delivered','Confirmed','Cancelled'] as $status)$this->post('/admin/orders/'.$o->id.'/status',['status'=>$status])->assertSessionHasNoErrors();$this->assertEquals('Cancelled',$o->fresh()->status);$this->assertEquals($before,$p->fresh()->stock);
 }
 public function test_confirm_return_requested_blocks_rider_and_gps_rules_disagree(){
  $this->buyer();$p=Product::first();$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]]);$this->place();$o=Order::first();$r=User::create(['name'=>'Audit Rider','email'=>'rider@example.test','password'=>bcrypt('RiderPass123!')]);$r->role_id=StoreRecord::in('roles')->where('name','delivery partner')->value('id');$r->save();DB::table('deliveries')->insert(['order_id'=>$o->id,'rider_id'=>$r->id,'promised_from'=>now(),'promised_to'=>now()->addHour(),'created_at'=>now(),'updated_at'=>now()]);Commerce::status($o,'Delivered',true);Commerce::status($o,'Return requested');$this->assertNull(Delivery::availableRider());$this->actingAs($r)->postJson('/rider/orders/'.$o->id,['latitude'=>19.1,'longitude'=>72.8])->assertOk();
 }
 public function test_confirm_duplicate_overlapping_slots_are_accepted(){
  $this->admin();$zone=DB::table('delivery_zones')->insertGetId(['name'=>'Audit','pincodes'=>'400001','opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'active'=>true,'express'=>true,'created_at'=>now(),'updated_at'=>now()]);$slot=['zone_id'=>$zone,'starts_at'=>'2026-10-06T12:00','ends_at'=>'2026-10-06T13:00','capacity'=>1];$this->post('/admin/delivery/slots',$slot)->assertSessionHasNoErrors();$this->post('/admin/delivery/slots',$slot)->assertSessionHasNoErrors();$this->assertEquals(2,DB::table('delivery_slots')->count());
 }
 public function test_confirm_expired_product_cannot_be_deactivated_in_editor(){
  $p=Product::first();$p->update(['expires_on'=>'2026-10-05']);$this->admin();$this->put('/admin/products/'.$p->id,['name'=>$p->name,'sku'=>$p->sku,'price'=>$p->price/100,'stock'=>$p->stock,'expires_on'=>$p->expires_on,'variants'=>$p->variants])->assertSessionHasErrors('expires_on');$this->assertTrue($p->fresh()->active);
 }
 public function test_confirm_admin_search_is_ignored_for_inventory_and_reviews(){
  $u=$this->buyer();$p=Product::first();DB::table('inventory')->insert(['product_id'=>$p->id,'adjustment'=>1,'reason'=>'AUDITVISIBLE','user_id'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);$this->admin();$this->get('/admin/inventory?q=DOESNOTEXIST')->assertOk()->assertSee('AUDITVISIBLE');
 }
 public function test_confirm_deleted_product_cart_is_handled_without_server_error(){
  $this->buyer();$p=Product::first();$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]]);$p->delete();$this->get('/cart')->assertOk()->assertSee('Unavailable product');
 }
 public function test_confirm_standard_shipping_rate_can_be_used_for_express(){
  $this->buyer();$p=Product::first();$p->update(['delivery_type'=>'dairy','delivery_minutes'=>60,'price'=>6500]);$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]]);DB::table('delivery_zones')->insert(['name'=>'Audit area','pincodes'=>'400001','opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'express'=>true,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);$r=User::create(['name'=>'Rider','email'=>'rider@example.test','password'=>bcrypt('RiderPass123!')]);$r->role_id=StoreRecord::in('roles')->where('name','delivery partner')->value('id');$r->save();$this->post('/checkout',['key'=>(string)Str::uuid(),'address_id'=>Address::first()->id,'shipping_id'=>StoreRecord::in('shipping_methods')->where('name','Standard delivery')->value('id'),'email'=>'audit@example.test','payment'=>'Test card / UPI','delivery_choice'=>'express'])->assertSessionHasNoErrors();$this->assertEquals(9900,Order::first()->shipping);
 }
}
