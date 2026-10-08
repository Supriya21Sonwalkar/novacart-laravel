<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\Product;
use App\StoreRecord;
class AdminWorkspaceTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(\StoreSeeder::class);$this->actingAs(User::where('email','admin@novacart.test')->first());}
 public function test_dashboard_has_actionable_navigation_and_search(){ $this->get('/admin')->assertOk()->assertSee('Orders to prepare')->assertSee('Expiring within 7 days')->assertSee('Orders &amp; Delivery',false)->assertSee('Search workspace')->assertSee('Create outward');$p=Product::first();$this->get('/admin/workspace/search?q='.urlencode($p->sku))->assertOk()->assertSee($p->name);$this->get('/admin/products?q='.urlencode($p->sku).'&sort=name&per_page=30')->assertOk()->assertSee($p->name);$this->get('/admin/products?sort=invalid')->assertSessionHasErrors('sort');}
 public function test_alert_filters_match_real_products(){ $p=Product::first();$p->stock=2;$p->expires_on=today()->addDays(2)->toDateString();$p->save();$this->assertEquals(1,$this->get('/admin/products?stock=low')->assertOk()->viewData('rows')->count(),'low filter');$this->assertEquals(1,$this->get('/admin/products?stock=expiring')->assertOk()->viewData('rows')->count(),'expiry filter');$this->assertEquals(0,$this->get('/admin/products?stock=low&q=not-existing')->assertOk()->viewData('rows')->count());}
 public function test_staff_search_hides_other_modules_and_customer_access_is_denied(){ $role=StoreRecord::in('roles')->create(['name'=>'Catalogue staff','data'=>['permissions'=>['dashboard','products']]]);$u=User::create(['name'=>'Staff','email'=>'staff@example.test','password'=>'hash']);$u->role_id=$role->id;$u->save();$this->actingAs($u);$this->get('/admin')->assertOk()->assertDontSee('Unread support messages')->assertDontSee('Process orders');$this->get('/admin/workspace/search?q=admin')->assertOk()->assertDontSee('admin@novacart.test');$this->get('/admin/orders')->assertForbidden();$u->role_id=null;$u->save();$this->get('/admin/workspace/search?q=test')->assertForbidden();auth()->logout();$this->get('/admin/workspace/search')->assertRedirect('/admin/login');}
 public function test_admin_login_has_password_visibility_and_inline_feedback(){ auth()->logout();$this->get('/admin/login')->assertOk()->assertSee('show-admin-password')->assertSee('current-password')->assertDontSee('href="/register"',false);$this->post('/admin/login',['email'=>'missing@example.test','password'=>'wrong'])->assertSessionHasErrors('email');$this->get('/admin/login')->assertOk()->assertSee('role="alert"',false);}
}
