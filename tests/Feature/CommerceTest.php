<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\User;
use App\Product;
use App\Address;
use App\Order;
use App\StoreRecord;
class CommerceTest extends TestCase {
 use RefreshDatabase;
 private function assertDatabaseCount($table,$count){$this->assertEquals($count,DB::table($table)->count());}
 protected function setUp():void {parent::setUp();$this->seed(\StoreSeeder::class);}
 private function customer($email='buyer@example.test'){$u=User::create(['name'=>'Customer','email'=>$email,'password'=>Hash::make('BuyerPass123!')]);$u->role_id=StoreRecord::in('roles')->where('name','customer')->value('id');$u->save();return $u;}
 private function addressData(){return ['name'=>'Buyer','phone'=>'9876543210','address'=>'12 Test Road','city'=>'Mumbai','state'=>'Maharashtra','pincode'=>'400001','type'=>'Home','is_default'=>1,'return_to'=>'checkout'];}
 private function checkoutData($address){return ['key'=>(string)Str::uuid(),'address_id'=>$address->id,'shipping_id'=>StoreRecord::in('shipping_methods')->first()->id,'payment'=>'Test card / UPI','email'=>'buyer@example.test'];}
 private function fillCart(){ $p=Product::first();$p->update(['price'=>150000,'stock'=>3]);$this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>$p->options[0]])->assertSessionHasNoErrors();return $p; }
 public function test_customer_pages_and_all_admin_modules_render(){
  foreach(['/','/shop','/shop?sort=popular&rating=4','/products/'.Product::first()->id,'/cart','/orders','/notifications','/help','/login','/register','/password/reset','/addresses/new'] as $url)$this->get($url)->assertOk();
  $this->actingAs(User::where('email','admin@novacart.test')->first());
  foreach(['/account','/wishlist','/admin','/admin/products/create','/admin/settings/1/edit'] as $url)$this->get($url)->assertOk();
  foreach(array_keys(config('store.modules')) as $module)$this->get('/admin/'.$module)->assertOk();
 }
 public function test_guest_checkout_uses_server_prices_and_is_idempotent(){
  $p=$this->fillCart();$this->post('/addresses',$this->addressData())->assertRedirect('/checkout');$a=Address::first();
  $this->post('/coupon',['coupon'=>'WELCOME10'])->assertSessionHasNoErrors();$this->get('/checkout')->assertOk();
  $input=$this->checkoutData($a)+['total'=>1,'price'=>1];$this->post('/checkout',$input)->assertSessionHasNoErrors();
  $o=Order::first();$this->assertEquals(151650,$o->total);$this->assertEquals(150000,$o->items->first()->price);$this->assertEquals(2,$p->fresh()->stock);
  $this->assertDatabaseCount('payments',1);$this->assertDatabaseCount('coupon_usages',1);$this->assertDatabaseCount('cart_items',0);
  $this->post('/checkout',$input)->assertRedirect('/orders/'.$o->id);$this->assertDatabaseCount('orders',1);$this->assertEquals(2,$p->fresh()->stock);
  $this->get('/orders/'.$o->id)->assertOk();$this->get('/orders/'.$o->id.'?print=1')->assertOk();
 }
 public function test_stock_and_variant_validation_prevents_overcommit(){
  $p=Product::first();$p->update(['stock'=>1]);$this->post('/cart/add/'.$p->id,['quantity'=>2,'variant'=>$p->options[0]])->assertSessionHasErrors('store');
  $this->post('/cart/add/'.$p->id,['quantity'=>1,'variant'=>'fake'])->assertSessionHasErrors('store');$this->assertDatabaseCount('cart_items',0);
 }
 public function test_checkout_rechecks_stock_and_keeps_cart_after_failure(){
  $p=$this->fillCart();$this->post('/addresses',$this->addressData());$input=$this->checkoutData(Address::first());$p->update(['stock'=>0]);
  $this->post('/checkout',$input)->assertSessionHasErrors('store');$this->assertDatabaseCount('orders',0);$this->assertDatabaseCount('cart_items',1);$this->assertDatabaseCount('payments',0);
 }
 public function test_customer_cancellation_restocks_only_once(){
  $p=$this->fillCart();$this->post('/addresses',$this->addressData());$this->post('/checkout',$this->checkoutData(Address::first()));$o=Order::first();
  $this->post('/orders/'.$o->id.'/status',['status'=>'Cancelled'])->assertSessionHasNoErrors();$this->assertEquals(3,$p->fresh()->stock);
  $this->post('/orders/'.$o->id.'/status',['status'=>'Cancelled'])->assertSessionHasErrors('store');$this->assertEquals(3,$p->fresh()->stock);
 }
 public function test_customer_cannot_access_admin_or_other_customers_data(){
  $a=$this->customer();$b=$this->customer('other@example.test');$this->actingAs($a);$this->fillCart();$this->post('/addresses',$this->addressData());$address=Address::first();$this->post('/checkout',$this->checkoutData($address));$o=Order::first();
  $this->actingAs($b);$this->get('/admin')->assertForbidden();$this->get('/admin/products')->assertForbidden();$this->get('/orders/'.$o->id)->assertForbidden();$this->post('/orders/'.$o->id.'/status',['status'=>'Cancelled'])->assertForbidden();$this->get('/addresses/'.$address->id.'/edit')->assertNotFound();
 }
 public function test_admin_can_create_edit_and_soft_delete_products(){
  $this->actingAs(User::where('email','admin@novacart.test')->first());$v=['name'=>'Own product','sku'=>'OWN-001','price'=>999.50,'old_price'=>1299,'stock'=>8,'variants'=>'Black,White','active'=>1,'featured'=>1];
  $this->post('/admin/products',$v)->assertRedirect('/admin/products')->assertSessionHasNoErrors();$p=Product::where('sku','OWN-001')->firstOrFail();$this->assertEquals(99950,$p->price);$this->get('/products/'.$p->id)->assertOk();
  $v['stock']=5;$this->put('/admin/products/'.$p->id,$v)->assertSessionHasNoErrors();$this->assertEquals(5,$p->fresh()->stock);$this->delete('/admin/products/'.$p->id)->assertSessionHasNoErrors();$this->get('/products/'.$p->id)->assertNotFound();$this->assertSoftDeleted('products',['id'=>$p->id]);
 }
 public function test_registration_cannot_assign_an_admin_role(){
  Notification::fake();$this->post('/register',['name'=>'New buyer','email'=>'new@example.test','password'=>'BuyerPass123!','password_confirmation'=>'BuyerPass123!','role_id'=>1,'active'=>1])->assertRedirect('/account');
  $u=User::where('email','new@example.test')->firstOrFail();$this->assertFalse($u->canManage('products'));Notification::assertSentTo($u,\Illuminate\Auth\Notifications\VerifyEmail::class);
 }
 public function test_reviews_require_moderation_and_ownership(){
  $a=$this->customer();$p=Product::first();$this->actingAs($a);$this->post('/reviews/'.$p->id,['rating'=>5,'title'=>'Great','text'=>'Works well','approved'=>1])->assertSessionHasNoErrors();$r=\App\Review::first();$this->assertFalse($r->approved);$this->assertEquals(0,$p->rating);
  $this->actingAs($this->customer('other@example.test'));$this->delete('/reviews/'.$r->id)->assertNotFound();
  $this->actingAs(User::where('email','admin@novacart.test')->first());$this->put('/admin/reviews/'.$r->id,['approved'=>1,'response'=>'Thank you'])->assertSessionHasNoErrors();$this->assertEquals(5,$p->fresh()->rating);
 }
 public function test_admin_updates_order_status_and_customer_returns_after_delivery(){
  $this->fillCart();$this->post('/addresses',$this->addressData());$this->post('/checkout',$this->checkoutData(Address::first()));$o=Order::first();
  $this->post('/orders/'.$o->id.'/status',['status'=>'Return requested'])->assertSessionHasErrors('store');
  $this->actingAs(User::where('email','admin@novacart.test')->first());$this->post('/admin/orders/'.$o->id.'/status',['status'=>'Delivered'])->assertSessionHasNoErrors();$this->assertEquals('Delivered',$o->fresh()->status);
 }
 public function test_admin_creates_users_and_permissions_limit_staff(){
  $this->actingAs(User::where('email','admin@novacart.test')->first());$this->get('/admin/users/create')->assertOk();
  $role=StoreRecord::in('roles')->create(['name'=>'catalogue staff','data'=>['permissions'=>['products']]]);
  $this->post('/admin/users',['name'=>'Staff','email'=>'staff@example.test','role_id'=>$role->id,'password'=>'StaffPass123!','password_confirmation'=>'StaffPass123!','active'=>1])->assertSessionHasNoErrors();
  $u=User::where('email','staff@example.test')->firstOrFail();$this->actingAs($u);$this->get('/admin/products')->assertOk();$this->get('/admin/users')->assertForbidden();$this->get('/admin/orders')->assertForbidden();
 }
 public function test_password_reset_token_changes_the_password(){
  Notification::fake();$u=$this->customer();$this->post('/password/email',['email'=>$u->email])->assertSessionHasNoErrors();
  $sent=Notification::sent($u,\Illuminate\Auth\Notifications\ResetPassword::class)->first();$this->assertNotNull($sent);
  $this->post('/password/reset',['email'=>$u->email,'token'=>$sent->token,'password'=>'UpdatedPass123!','password_confirmation'=>'UpdatedPass123!'])->assertRedirect('/login');
  $this->assertTrue(Hash::check('UpdatedPass123!',$u->fresh()->password));
 }
 public function test_product_image_upload_is_saved_to_public_storage(){
  \Illuminate\Support\Facades\Storage::fake('public');$this->actingAs(User::where('email','admin@novacart.test')->first());
  $this->post('/admin/products',['name'=>'Uploaded product','sku'=>'IMG-001','price'=>10,'stock'=>2,'active'=>1,'image_upload'=>\Illuminate\Http\UploadedFile::fake()->image('product.jpg')])->assertSessionHasNoErrors();
  $p=Product::where('sku','IMG-001')->firstOrFail();$this->assertStringStartsWith('/storage/products/',$p->image);\Illuminate\Support\Facades\Storage::disk('public')->assertExists(substr($p->image,9));
  $this->post('/admin/products',['name'=>'Unsafe upload','sku'=>'IMG-002','price'=>10,'stock'=>2,'active'=>1,'image_upload'=>\Illuminate\Http\UploadedFile::fake()->createWithContent('disguised.jpg','<?php echo "unsafe";')])->assertSessionHasErrors('image_upload');$this->assertDatabaseMissing('products',['sku'=>'IMG-002']);
 }
 public function test_login_claims_guest_addresses_without_a_cart(){
  $u=$this->customer();$this->post('/addresses',$this->addressData());$this->post('/login',['email'=>$u->email,'password'=>'BuyerPass123!'])->assertSessionHasNoErrors();$this->assertEquals($u->id,Address::first()->user_id);
 }
}
