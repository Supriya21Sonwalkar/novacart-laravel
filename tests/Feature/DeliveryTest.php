<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Product;
use App\StoreRecord;
use App\User;
use App\Order;
use App\Address;
use App\Services\Delivery;
use App\Services\CustomerProfile;
use Carbon\Carbon;
class DeliveryTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();Carbon::setTestNow(Carbon::parse('2026-10-06 10:00:00'));$this->seed(\StoreSeeder::class);}
 protected function tearDown():void{Carbon::setTestNow();parent::tearDown();}
 private function zone(){return DB::table('delivery_zones')->insertGetId(['name'=>'Mumbai','pincodes'=>'400001','opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'express'=>true,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);}
 private function rider($email='rider@test.example'){$u=User::create(['name'=>'Delivery Partner','email'=>$email,'password'=>bcrypt('RiderPass123!')]);$u->role_id=StoreRecord::in('roles')->where('name','delivery partner')->value('id');$u->save();return $u;}
 private function buyer($email='buyer@test.example'){$u=User::create(['name'=>'Buyer','email'=>$email,'password'=>bcrypt('BuyerPass123!')]);$u->role_id=StoreRecord::in('roles')->where('name','customer')->value('id');$u->save();CustomerProfile::save($u,['name'=>'Buyer','email'=>$email,'phone'=>'98'.str_pad($u->id,8,'0',STR_PAD_LEFT),'address'=>'12 Test Road','city'=>'Mumbai','state'=>'Maharashtra','pincode'=>'400001','type'=>'Home']);return $u;}
 private function slot($zone,$start='2026-10-06 12:00:00',$end='2026-10-06 13:00:00',$capacity=1){return DB::table('delivery_slots')->insertGetId(['zone_id'=>$zone,'starts_at'=>$start,'ends_at'=>$end,'capacity'=>$capacity,'created_at'=>now(),'updated_at'=>now()]);}
 private function cart($buyer,$type='dairy'){$this->actingAs($buyer);$p=Product::first();$p->update(['delivery_type'=>$type,'delivery_minutes'=>60,'stock'=>20]);$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]])->assertSessionHasNoErrors();return $p;}
 private function place($choice){return $this->post('/checkout',['key'=>(string)Str::uuid(),'address_id'=>Address::where('user_id',auth()->id())->first()->id,'shipping_id'=>StoreRecord::in('shipping_methods')->first()->id,'email'=>auth()->user()->email,'payment'=>'Test card / UPI','delivery_choice'=>$choice]);}
 public function test_public_parent_category_and_keyword_search_include_subcategories(){
  $root=StoreRecord::in('categories')->create(['name'=>'Grocery','data'=>[]]);$child=StoreRecord::in('categories')->create(['name'=>'Dairy','data'=>['parent_id'=>$root->id]]);$p=Product::first();$p->update(['name'=>'Fresh Milk','category_id'=>$child->id,'tags'=>'dudh,दूध']);
  $this->get('/shop?category='.$root->id)->assertOk()->assertSee('Fresh Milk');$this->get('/shop?q=Dairy')->assertOk()->assertSee('Fresh Milk');$this->get('/search/suggestions?q=dudh')->assertOk()->assertJsonFragment(['name'=>'Fresh Milk']);$this->get('/shop?category='.$root->id.'&min=99999')->assertOk()->assertSee('No matching products');
 }
 public function test_dairy_options_respect_rider_opening_hours_and_same_day(){
  $zone=$this->zone();$p=Product::first();$p->update(['delivery_type'=>'dairy']);$items=collect([(object)['product'=>$p]]);$this->slot($zone);$this->slot($zone,'2026-10-07 12:00:00','2026-10-07 13:00:00');
  $this->assertCount(0,Delivery::options($items,'400001'));$this->rider();$this->assertEquals('express',Delivery::options($items,'400001')[0]['value']);$this->assertCount(1,Delivery::options($items,'400001'));$this->assertEquals([],Delivery::options($items,'999999'));Carbon::setTestNow('2026-10-06 23:00:00');$this->assertEquals([],Delivery::options($items,'400001'));
 }
 public function test_express_checkout_assigns_rider_and_tracking_is_private(){
  $this->zone();$rider=$this->rider();$buyer=$this->buyer();$this->cart($buyer);$this->get('/checkout')->assertOk()->assertSee('Delivery date');$this->place('express')->assertSessionHasNoErrors();$order=Order::first();$d=DB::table('deliveries')->first();$this->assertEquals($rider->id,$d->rider_id);$this->assertEquals('2026-10-06 11:00:00',$d->promised_to);$this->get('/orders/'.$order->id)->assertOk();$this->get('/orders/'.$order->id.'/tracking')->assertOk();$other=$this->buyer('other@test.example');$this->actingAs($other)->get('/orders/'.$order->id.'/tracking')->assertForbidden();$this->post('/rider/orders/'.$order->id,['latitude'=>19,'longitude'=>72])->assertForbidden();
  $this->actingAs($rider)->get('/rider')->assertOk();$this->postJson('/rider/orders/'.$order->id,['latitude'=>19.1,'longitude'=>72.8,'status'=>'Picked up','eta_minutes'=>25])->assertOk();$this->assertEquals('Picked up',$order->fresh()->status);$this->postJson('/rider/orders/'.$order->id,['status'=>'Delivered'])->assertStatus(422);$this->postJson('/rider/orders/'.$order->id,['status'=>'On the way'])->assertOk();$this->postJson('/rider/orders/'.$order->id,['status'=>'Delivered'])->assertOk();$this->actingAs($buyer)->get('/orders/'.$order->id.'/tracking')->assertJsonFragment(['latitude'=>null]);
 }
 public function test_slot_capacity_cannot_be_overbooked_and_cancel_frees_it(){
  $zone=$this->zone();$slot=$this->slot($zone);$buyer=$this->buyer();$this->cart($buyer,'fresh');$this->place((string)$slot)->assertSessionHasNoErrors();$first=Order::first();$this->cart($this->buyer('second@test.example'),'fresh');$this->place((string)$slot)->assertSessionHasErrors('store');$this->assertEquals(1,Order::count());$this->actingAs($buyer)->post('/orders/'.$first->id.'/status',['status'=>'Cancelled'])->assertSessionHasNoErrors();$this->actingAs(User::where('email','second@test.example')->first());$this->place((string)$slot)->assertSessionHasNoErrors();$this->assertEquals(2,Order::count());
 }
 public function test_expired_food_and_missing_delivery_choice_cannot_bypass_checkout(){
  $this->zone();$this->rider();$buyer=$this->buyer();$p=$this->cart($buyer);$this->place('')->assertSessionHasErrors('store');$p->update(['expires_on'=>'2026-10-05']);$this->place('express')->assertSessionHasErrors('store');$this->assertEquals(0,Order::count());
 }
 public function test_last_fifo_unit_can_be_scheduled_and_checkout_retry_does_not_duplicate_it(){
  $this->zone();$this->rider();$buyer=$this->buyer();$p=$this->cart($buyer);
  $p->update(['stock'=>0,'expires_on'=>'2026-10-05']);
  DB::transaction(function()use($p){\App\Services\BatchStock::receive($p,1,['batch_number'=>'LAST-DAIRY','received_at'=>now(),'expires_on'=>'2026-10-07','unit_cost'=>5000]);});
  $address=Address::where('user_id',$buyer->id)->first();
  $this->getJson('/delivery/options?address_id='.$address->id)->assertOk()->assertJsonFragment(['value'=>'express','required'=>true]);
  $input=['key'=>(string)Str::uuid(),'address_id'=>$address->id,'shipping_id'=>StoreRecord::in('shipping_methods')->first()->id,'email'=>$buyer->email,'payment'=>'Test card / UPI','delivery_choice'=>'express'];
  $this->post('/checkout',$input)->assertSessionHasNoErrors();$this->post('/checkout',$input)->assertSessionHasNoErrors();
  $this->assertEquals(1,Order::count());$this->assertEquals(1,DB::table('deliveries')->count());$this->assertEquals(1,DB::table('batch_allocations')->count());$this->assertEquals(0,$p->fresh()->stock);
  $this->get('/orders/'.Order::first()->id)->assertOk()->assertSee('Track delivery');
 }
 public function test_unsupported_delivery_address_rolls_back_order_and_keeps_cart(){
  $this->zone();$this->rider();$buyer=$this->buyer();$p=$this->cart($buyer);$before=$p->stock;
  Address::where('user_id',$buyer->id)->update(['pincode'=>'999999']);
  $this->place('express')->assertSessionHasErrors('store');
  $this->assertEquals(0,Order::count());$this->assertEquals(0,DB::table('deliveries')->count());$this->assertEquals(0,DB::table('batch_allocations')->count());$this->assertEquals($before,$p->fresh()->stock);$this->assertEquals(1,DB::table('cart_items')->count());
 }
 public function test_category_cycle_rejects_inactive_descendants_and_allows_valid_reparenting(){
  $this->actingAs(User::where('email','admin@novacart.test')->first());
  $root=StoreRecord::in('categories')->create(['name'=>'Root','data'=>[]]);
  $child=StoreRecord::in('categories')->create(['name'=>'Child','active'=>false,'data'=>['parent_id'=>$root->id]]);
  $leaf=StoreRecord::in('categories')->create(['name'=>'Leaf','data'=>['parent_id'=>$child->id]]);
  $this->put('/admin/categories/'.$root->id,['name'=>'Root','parent_id'=>$leaf->id,'active'=>1])->assertSessionHasErrors('store');
  $this->assertEmpty($root->fresh()->data['parent_id']??null);
  $this->put('/admin/categories/'.$leaf->id,['name'=>'Leaf','parent_id'=>$root->id,'active'=>1])->assertSessionHasNoErrors();
  $this->assertEquals($root->id,$leaf->fresh()->data['parent_id']);
 }
 public function test_admin_delivery_operations_and_category_cycle_are_validated(){
  $admin=User::where('email','admin@novacart.test')->first();$this->actingAs($admin)->get('/admin/delivery')->assertOk();$this->post('/admin/delivery/zones',['name'=>'Mumbai','pincodes'=>'400001','opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'active'=>1])->assertSessionHasNoErrors();$zone=DB::table('delivery_zones')->value('id');$this->post('/admin/delivery/slots',['zone_id'=>$zone,'starts_at'=>'2026-10-06T12:00','ends_at'=>'2026-10-06T13:00','capacity'=>1])->assertSessionHasNoErrors();$this->get('/admin/delivery')->assertOk();
  $root=StoreRecord::in('categories')->create(['name'=>'Root','data'=>[]]);$child=StoreRecord::in('categories')->create(['name'=>'Child','data'=>['parent_id'=>$root->id]]);$this->put('/admin/categories/'.$root->id,['name'=>'Root','parent_id'=>$child->id,'active'=>1])->assertSessionHasErrors('store');
 }
}
