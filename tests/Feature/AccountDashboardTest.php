<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\User;
class AccountDashboardTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(\StoreSeeder::class);Notification::fake();}
 private function register(){return $this->post('/register',['name'=>'New Customer','email'=>'account@example.test','phone'=>'9876543210','address'=>'12 Customer Road','city'=>'Mumbai','state'=>'Maharashtra','pincode'=>'400001','type'=>'Home','password'=>'CustomerPass123!','password_confirmation'=>'CustomerPass123!']);}
 public function test_registration_lands_on_dashboard_without_pincode_selector(){ $this->register()->assertRedirect('/account');$this->get('/account')->assertOk()->assertSee('Your Orders')->assertSee('Login &amp; Security',false)->assertSee('Payment Options')->assertSee('Contact Us')->assertDontSee('id="delivery-pin"',false);$this->get('/account/security')->assertOk()->assertSee('Profile &amp; delivery details',false);}
 public function test_payment_preference_is_validated_and_scoped_to_signed_in_user(){ $this->get('/account/payments')->assertRedirect('/login');$this->register();$this->get('/account/payments')->assertOk();$this->post('/account/payments',['payment_preference'=>'Cash on delivery (test)'])->assertSessionHasNoErrors();$this->assertEquals('Cash on delivery (test)',User::where('email','account@example.test')->value('payment_preference'));$this->post('/account/payments',['payment_preference'=>'Unknown'])->assertSessionHasErrors('payment_preference');}
 public function test_admin_has_sign_in_only_and_customers_cannot_use_it(){ $this->get('/admin/login')->assertOk()->assertDontSee('Create an account')->assertDontSee('href="/register"',false);$this->get('/admin')->assertRedirect('/admin/login');$this->register();$this->post('/logout');$this->post('/admin/login',['email'=>'account@example.test','password'=>'CustomerPass123!'])->assertSessionHasErrors('email');$this->assertGuest();$this->post('/admin/login',['email'=>'admin@novacart.test','password'=>'DemoStore!2026'])->assertRedirect('/admin');$this->get('/admin')->assertOk();}
 public function test_side_menu_lists_nested_catalogue_categories(){ $this->get('/shop')->assertOk()->assertSee('id="store-menu"',false)->assertSee('Shop by Category')->assertSee('Browse all categories');}
}
