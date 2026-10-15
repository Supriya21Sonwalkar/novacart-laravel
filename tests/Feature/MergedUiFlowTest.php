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
class MergedUiFlowTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(\StoreSeeder::class);$this->actingAs(User::where('email','admin@novacart.test')->first());}
 public function test_selected_purchase_receipt_uses_shared_fifo_and_ledger_once(){
  $vendor=Vendor::create(['name'=>'Merged supplier','active'=>true]);$product=Product::first();$before=$product->stock;
  $po=PurchaseOrder::create(['number'=>'MERGED-PO','request_key'=>(string)Str::uuid(),'vendor_id'=>$vendor->id,'created_by'=>auth()->id(),'order_date'=>today()->toDateString()]);$item=$po->items()->create(['product_id'=>$product->id,'name'=>$product->name,'sku'=>$product->sku,'category_name'=>'Test','subcategory_name'=>'Test','existing_stock'=>$before,'quantity'=>5]);
  $this->get('/admin/inward/create?purchase_order_id='.$po->id)->assertOk()->assertSee('Merged supplier')->assertSee('manual-receipt-batches');$this->getJson('/admin/purchase-list/vendors/'.$vendor->id.'/purchase-orders')->assertOk()->assertJsonFragment(['number'=>'MERGED-PO']);$this->getJson('/admin/purchase-list/purchase-orders/'.$po->id.'/items')->assertOk()->assertJsonFragment(['pending_quantity'=>5]);
  $payload=['number'=>'MERGED-IN','vendor_id'=>$vendor->id,'purchase_order_id'=>$po->id,'supplier_invoice_no'=>'MERGED-BILL','inward_date'=>today()->toDateString(),'invoice_date'=>today()->toDateString(),'warehouse'=>'Main inventory','received_by'=>'Admin','received_items'=>[$item->id=>['selected'=>'1','quantity'=>2,'batch_number'=>'MERGED-BATCH','unit_cost'=>'50.25','gst_percent'=>0]]];
  $this->post('/admin/inward',$payload)->assertSessionHasNoErrors();$this->assertEquals($before+2,$product->fresh()->stock);$this->assertEquals(2,$item->fresh()->received_quantity);$this->assertEquals('Partially Received',$po->fresh()->status);$this->assertEquals($po->id,DB::table('inwards')->value('purchase_order_id'));$this->assertEquals($before+2,DB::table('inventory')->where('product_id',$product->id)->orderByDesc('id')->value('after_quantity'));
  $payload['number']='OVER-RECEIPT';$payload['received_items'][$item->id]['batch_number']='OVER-BATCH';$payload['received_items'][$item->id]['quantity']=4;$this->post('/admin/inward',$payload)->assertSessionHasErrors('store');$this->assertEquals($before+2,$product->fresh()->stock);$this->assertEquals(1,DB::table('inwards')->count());
 }
 public function test_express_rider_must_serve_the_zone_and_assignment_requires_admin_permission(){
  $zone=DB::table('delivery_zones')->insertGetId(['name'=>'Zone A','city'=>'Mumbai','pincodes'=>'400001','opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'express'=>true,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);$other=DB::table('delivery_zones')->insertGetId(['name'=>'Zone B','city'=>'Pune','pincodes'=>'411001','opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'express'=>true,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
  $rider=User::create(['name'=>'Zone rider','email'=>'zone-rider@example.test','password'=>'hash']);$rider->role_id=StoreRecord::in('roles')->where('name','delivery partner')->value('id');$rider->save();$this->assertNull(\App\Services\Delivery::availableRider($zone));$this->post('/admin/delivery/riders/'.$rider->id.'/zones',['zone_ids'=>[$zone]])->assertSessionHasNoErrors();$this->assertEquals($rider->id,\App\Services\Delivery::availableRider($zone)->id);$this->assertNull(\App\Services\Delivery::availableRider($other));$this->actingAs($rider)->post('/admin/delivery/riders/'.$rider->id.'/zones',['zone_ids'=>[$other]])->assertForbidden();
 }
 public function test_tracking_is_generated_once_and_real_courier_reference_can_be_saved(){
  $product=Product::first();$order=\App\Order::create(['number'=>'TRACK-ORDER','idempotency_key'=>(string)Str::uuid(),'session_key'=>'test','email'=>'buyer@example.test','status'=>'Placed','subtotal'=>$product->price,'total'=>$product->price,'shipping_method_id'=>1,'payment_method'=>'test','timeline'=>[]]);$order->items()->create(['product_id'=>$product->id,'name'=>$product->name,'sku'=>$product->sku,'image'=>$product->image,'variant'=>'Default','quantity'=>1,'price'=>$product->price]);
  $this->post('/admin/outward',['request_key'=>(string)Str::uuid(),'type'=>'Order Dispatch','outward_date'=>today()->toDateString(),'source'=>'Main inventory','order_id'=>$order->id,'intent'=>'confirm'])->assertSessionHasNoErrors();$out=\App\Outward::first();$url='/admin/outward/'.$out->id.'/dispatch-details';$this->post($url,['partner'=>'Store delivery'])->assertSessionHasNoErrors();$tracking=$out->fresh()->tracking_number;$this->assertStringStartsWith('NVC-',$tracking);$this->post($url,['partner'=>'Store delivery'])->assertSessionHasNoErrors();$this->assertEquals($tracking,$out->fresh()->tracking_number);$this->post($url,['partner'=>'External courier','tracking_number'=>'REAL-COURIER-123'])->assertSessionHasNoErrors();$this->assertEquals('REAL-COURIER-123',$out->fresh()->tracking_number);
 }
}
