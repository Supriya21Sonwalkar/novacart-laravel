<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\User;
use App\Address;
use App\Product;
use App\StoreRecord;
use App\Services\CustomerProfile;
class ProfileTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(\StoreSeeder::class);Notification::fake();}
 private function buyer(){return User::create(['name'=>'Buyer','email'=>'profile@example.test','password'=>Hash::make('BuyerPass123!')]);}
 private function details(){return ['name'=>'Buyer','email'=>'profile@example.test','phone'=>'9876543210','address'=>'12 Test Street','city'=>'Mumbai','state'=>'Maharashtra','pincode'=>'400001','type'=>'Home'];}
 public function test_guest_and_incomplete_customer_cannot_add_or_checkout(){
  $p=Product::first();$payload=['quantity'=>1,'variant'=>$p->options[0]];
  $this->post('/cart/add/'.$p->id,$payload)->assertRedirect('/login');$this->get('/checkout')->assertRedirect('/login');
  $this->actingAs($this->buyer());foreach(['/cart/add/'.$p->id,'/checkout','/coupon'] as $path)$this->post($path,$payload+['profile_complete'=>true])->assertRedirect('/account');
  $this->get('/checkout')->assertRedirect('/account');$this->postJson('/cart/add/'.$p->id,$payload)->assertForbidden();
  $this->assertDatabaseMissing('cart_items',['product_id'=>$p->id]);$this->assertEquals(0,\App\Order::count());
 }
 public function test_complete_profile_unlocks_cart_and_saves_default_address(){
  $u=$this->buyer();$this->actingAs($u);$this->post('/account',$this->details())->assertRedirect('/account')->assertSessionHasNoErrors();
  $this->assertTrue(CustomerProfile::complete($u->fresh()));$this->assertDatabaseHas('addresses',['user_id'=>$u->id,'address'=>'12 Test Street','is_default'=>1]);
  $p=Product::first();$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]])->assertSessionHasNoErrors();$this->get('/checkout')->assertOk();
  $v=$this->details();$v['city']='Pune';$this->post('/account',$v)->assertSessionHasNoErrors();$this->assertEquals(1,Address::where('user_id',$u->id)->count());$this->assertEquals('Pune',CustomerProfile::address($u)->city);
 }
 public function test_missing_or_invalid_fields_cannot_complete_a_profile(){
  $u=$this->buyer();$this->actingAs($u);$v=$this->details();unset($v['address'],$v['city']);$v['phone']='abc';$v['pincode']='123';
  $this->post('/account',$v)->assertSessionHasErrors(['address','city','phone','pincode']);$this->assertFalse(CustomerProfile::complete($u->fresh()));$this->assertEquals(0,Address::count());
 }
 public function test_login_redirects_incomplete_user_to_profile(){
  $this->buyer();$this->withSession(['url.intended'=>'/checkout'])->post('/login',['email'=>'profile@example.test','password'=>'BuyerPass123!'])->assertRedirect('/account')->assertSessionHas('notice');$this->get('/account')->assertOk()->assertSee('Your profile is incomplete');
 }
 public function test_registration_requires_profile_and_cannot_spoof_completion(){
  $this->post('/register',['name'=>'Buyer','email'=>'profile@example.test','password'=>'BuyerPass123!','password_confirmation'=>'BuyerPass123!','profile_complete'=>true])->assertSessionHasErrors(['phone','address','city','state','pincode','type']);$this->assertDatabaseMissing('users',['email'=>'profile@example.test']);
  $this->post('/register',$this->details()+['password'=>'BuyerPass123!','password_confirmation'=>'BuyerPass123!'])->assertSessionHasNoErrors()->assertRedirect('/account');$u=User::where('email','profile@example.test')->firstOrFail();$this->assertTrue(CustomerProfile::complete($u));$this->assertFalse($u->canManage('products'));
 }
 public function test_deleting_default_address_blocks_existing_cart_checkout_and_reorder(){
  $u=$this->buyer();$this->actingAs($u);$this->post('/account',$this->details());$p=Product::first();$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]]);$a=CustomerProfile::address($u);
  $this->post('/checkout',['key'=>(string)\Illuminate\Support\Str::uuid(),'address_id'=>$a->id,'shipping_id'=>StoreRecord::in('shipping_methods')->first()->id,'payment'=>'Test card / UPI','email'=>$u->email])->assertSessionHasNoErrors();$order=\App\Order::firstOrFail();
  $this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]])->assertSessionHasNoErrors();
  $this->delete('/addresses/'.$a->id);$this->get('/checkout')->assertRedirect('/account');$this->post('/checkout',[])->assertRedirect('/account');$this->post('/orders/'.$order->id.'/reorder')->assertRedirect('/account');$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]])->assertRedirect('/account');$this->assertEquals(1,\App\Order::count());
 }
 public function test_another_users_address_does_not_complete_profile(){
  $u=$this->buyer();$u->phone='9876543210';$u->save();$other=User::create(['name'=>'Other','email'=>'other@example.test','password'=>Hash::make('OtherPass123!')]);Address::create(array_diff_key($this->details(),['email'=>true])+['user_id'=>$other->id,'is_default'=>true]);$this->assertFalse(CustomerProfile::complete($u));
 }
 public function test_service_cannot_bypass_profile_gate(){
  $this->actingAs($this->buyer());$this->expectException(\Illuminate\Validation\ValidationException::class);\App\Services\Commerce::add(Product::first(),1,'Default');
 }
 public function test_checkout_service_cannot_bypass_profile_gate(){
  $this->actingAs($this->buyer());$this->expectException(\Illuminate\Validation\ValidationException::class);\App\Services\Commerce::checkout([]);
 }
 public function test_browsing_and_bag_remain_open_but_wishlist_writes_are_protected(){
  $p=Product::first();foreach(['/','/shop','/products/'.$p->id,'/cart'] as $url)$this->get($url)->assertOk()->assertSee('Complete your profile first');
  $u=$this->buyer();$this->actingAs($u);$this->get('/cart')->assertOk();$this->post('/wishlist/'.$p->id)->assertRedirect('/account');$this->assertEquals(0,\Illuminate\Support\Facades\DB::table('wishlist_items')->count());
  $this->post('/account',$this->details())->assertSessionHasNoErrors();$this->post('/wishlist/'.$p->id)->assertSessionHasNoErrors();$this->assertEquals(1,\Illuminate\Support\Facades\DB::table('wishlist_items')->count());$this->get('/shop')->assertSee('data-profile-complete="true"',false);
 }
}
