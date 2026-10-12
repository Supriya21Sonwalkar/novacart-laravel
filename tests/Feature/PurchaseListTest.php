<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Product;
use App\Vendor;
use App\User;
use App\PurchaseOrder;
use App\StoreRecord;
class PurchaseListTest extends TestCase {
 use RefreshDatabase;
 private $vendor,$category,$sub,$product;
 protected function setUp():void{
  parent::setUp();$this->seed(\StoreSeeder::class);$this->actingAs(User::where('email','admin@novacart.test')->first());
  $this->vendor=Vendor::create(['name'=>'ABC Suppliers','active'=>true,'data'=>['contact_person'=>'Supplier Contact','phone'=>'9876543210','email'=>'supplier@example.test','address'=>'Mumbai','gst_number'=>'GST-TEST']]);
  $this->category=StoreRecord::in('categories')->create(['name'=>'Purchase category','data'=>[]]);$this->sub=StoreRecord::in('categories')->create(['name'=>'Purchase subcategory','data'=>['parent_id'=>$this->category->id]]);
  $this->product=Product::first();$this->product->update(['category_id'=>$this->sub->id]);
 }
 private function payload(){return ['request_key'=>(string)Str::uuid(),'vendor_id'=>$this->vendor->id,'items'=>[['product_id'=>$this->product->id,'category_id'=>$this->category->id,'subcategory_id'=>$this->sub->id,'quantity'=>10]]];}
 public function test_page_vendor_details_and_permissions_use_existing_admin_controls(){
  Vendor::create(['name'=>'Inactive supplier','active'=>false]);
  $this->get('/admin/purchase-list')->assertOk()->assertSee('ABC Suppliers')->assertDontSee('Inactive supplier')->assertSee('PLACE ORDER');
  $this->getJson('/admin/purchase-list/vendors/'.$this->vendor->id)->assertOk()->assertJsonFragment(['contact_person'=>'Supplier Contact']);
  $role=StoreRecord::in('roles')->create(['name'=>'Purchaser','data'=>['permissions'=>['purchase-list']]]);$u=User::create(['name'=>'Buyer staff','email'=>'purchaser@example.test','password'=>'hash']);$u->role_id=$role->id;$u->save();$this->actingAs($u);
  $this->get('/admin/purchase-list')->assertOk()->assertSee('Purchase List');$this->get('/admin/vendors')->assertForbidden();
  $u->role_id=null;$u->save();$this->get('/admin/purchase-list')->assertForbidden();$this->getJson('/admin/purchase-list/products?category_id=1&subcategory_id=2')->assertForbidden();$this->post('/admin/purchase-list',$this->payload())->assertForbidden();
  auth()->logout();$this->get('/admin/purchase-list')->assertRedirect('/admin/login');
 }
 public function test_cascading_endpoints_only_include_active_children_and_matching_products(){
  StoreRecord::in('categories')->create(['name'=>'Inactive child','active'=>false,'data'=>['parent_id'=>$this->category->id]]);
  $this->getJson('/admin/purchase-list/subcategories?category_id='.$this->category->id)->assertOk()->assertExactJson([['id'=>$this->sub->id,'name'=>$this->sub->name]]);
  $other=Product::where('id','!=',$this->product->id)->first();
  $this->getJson('/admin/purchase-list/products?category_id='.$this->category->id.'&subcategory_id='.$this->sub->id)->assertOk()->assertJsonFragment(['sku'=>$this->product->sku,'stock'=>$this->product->available_stock])->assertJsonMissing(['sku'=>$other->sku]);
  $this->getJson('/admin/purchase-list/products?category_id=1&subcategory_id='.$this->sub->id)->assertStatus(422);
 }
 public function test_order_saves_multiple_products_and_is_idempotent_without_changing_stock(){
  $second=Product::where('id','!=',$this->product->id)->first();$second->update(['category_id'=>$this->sub->id]);
  $input=$this->payload();$input['items'][]=['product_id'=>$second->id,'category_id'=>$this->category->id,'subcategory_id'=>$this->sub->id,'quantity'=>15];
  $before=DB::table('products')->sum('stock');$batches=DB::table('product_batches')->count();$inventory=DB::table('inventory')->count();$orders=DB::table('orders')->count();
  $this->post('/admin/purchase-list',$input)->assertSessionHasNoErrors();$o=PurchaseOrder::firstOrFail();$this->assertStringStartsWith('PO-'.today()->format('Ymd').'-',$o->number);$this->assertEquals(2,$o->items()->count());$this->assertEquals(0,$o->items()->sum('received_quantity'));$this->assertEquals(25,$o->items->sum('pending_quantity'));
  $this->get('/admin/purchase-list/'.$o->id)->assertOk()->assertSee($o->number)->assertSee('ABC Suppliers')->assertSee('Pending Qty')->assertSee($this->product->sku);
  $this->post('/admin/purchase-list',$input)->assertSessionHasNoErrors();$this->assertEquals(1,PurchaseOrder::count());$this->assertEquals($before,DB::table('products')->sum('stock'));$this->assertEquals($batches,DB::table('product_batches')->count());$this->assertEquals($inventory,DB::table('inventory')->count());$this->assertEquals($orders,DB::table('orders')->count());
 }
 public function invalidQuantities(){return [[0],[-1],['abc'],[''],[1.5],[1000001]];}
 /** @dataProvider invalidQuantities */
 public function test_invalid_quantity_is_rejected($quantity){$v=$this->payload();$v['items'][0]['quantity']=$quantity;$this->post('/admin/purchase-list',$v)->assertSessionHasErrors('items.0.quantity');$this->assertEquals(0,PurchaseOrder::count());}
 public function test_duplicate_products_inactive_vendor_and_wrong_relationship_are_rejected(){
  $v=$this->payload();$v['items'][]=$v['items'][0];$this->post('/admin/purchase-list',$v)->assertSessionHasErrors();
  $v=$this->payload();$this->vendor->update(['active'=>false]);$this->post('/admin/purchase-list',$v)->assertSessionHasErrors('store');$this->vendor->update(['active'=>true]);
  $v['items'][0]['category_id']=1;$this->post('/admin/purchase-list',$v)->assertSessionHasErrors('store');
  $v=$this->payload();$v['items'][0]['product_id']=Product::where('id','!=',$this->product->id)->value('id');$this->post('/admin/purchase-list',$v)->assertSessionHasErrors('store');$this->assertEquals(0,PurchaseOrder::count());$this->assertEquals(0,DB::table('purchase_order_items')->count());
 }
 public function test_inactive_or_deleted_product_does_not_create_partial_order(){
  $other=Product::where('id','!=',$this->product->id)->first();$other->update(['category_id'=>$this->sub->id,'active'=>false]);$v=$this->payload();$v['items'][]=['product_id'=>$other->id,'category_id'=>$this->category->id,'subcategory_id'=>$this->sub->id,'quantity'=>1];
  $this->post('/admin/purchase-list',$v)->assertSessionHasErrors('store');$this->assertEquals(0,PurchaseOrder::count());$this->assertEquals(0,DB::table('purchase_order_items')->count());
 }
}
