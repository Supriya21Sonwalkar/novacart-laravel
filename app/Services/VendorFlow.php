<?php
namespace App\Services;
use App\Vendor;
use App\Product;
use App\Order;
use App\StoreRecord;
use Illuminate\Support\Facades\DB;
class VendorFlow {
 public static function notify($vendor,$message,$data=[]){
  if (!config('store_management.vendor_portal')) return;
  if($vendor&&($data['kind']??'orders')==='orders'&&isset($vendor->data['notify_orders'])&&!$vendor->data['notify_orders'])return;
  if($vendor&&($data['kind']??'orders')==='reviews'&&isset($vendor->data['notify_reviews'])&&!$vendor->data['notify_reviews'])return;
  if($vendor&&$vendor->user_id)StoreRecord::in('notifications')->create(['user_id'=>$vendor->user_id,'name'=>$message,'data'=>$data+['vendor_id'=>$vendor->id]]);
 }
 public static function recordInventory($product,$adjustment,$reason){$after=(int)$product->stock;DB::table('inventory')->insert(['product_id'=>$product->id,'adjustment'=>$adjustment,'before_quantity'=>$after-$adjustment,'after_quantity'=>$after,'reason'=>$reason,'user_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);}
 public static function captureOrder(Order $order){
  if (!config('store_management.vendor_portal')) return;
  $items=$order->items()->orderBy('id')->get();$gross=0;foreach($items as $i)$gross+=$i->price*$i->quantity;$running=0;
  foreach($items as $i){
   $p=Product::withTrashed()->find($i->product_id);$v=$p&&$p->vendor_id?Vendor::find($p->vendor_id):null;$line=$i->price*$i->quantity;$before=$running;$running+=$line;
   $part=function($amount)use($before,$running,$gross){return $gross?(int)round($amount*$running/$gross)-(int)round($amount*$before/$gross):0;};
   $discount=$part($order->discount);$commission=$v?(float)($v->data['commission_percent']??0):0;
   $i->update(['vendor_id'=>$v?$v->id:null,'vendor_discount'=>$discount,'vendor_tax'=>$part($order->tax),'vendor_shipping'=>$part($order->shipping),'vendor_commission'=>(int)round(($line-$discount)*$commission/100)]);
  }
  foreach($items->fresh()->filter(function($item){return $item->vendor_id!==null;})->pluck('vendor_id')->unique() as $id){$vendor=Vendor::find($id);DB::table('vendor_fulfilments')->insert(['vendor_id'=>$id,'order_id'=>$order->id,'status'=>'New','timeline'=>json_encode([['status'=>'New','at'=>now()->toIso8601String()]]),'created_at'=>now(),'updated_at'=>now()]);}
 }
 public static function orderItems($vendorId){return DB::table('order_items')->join('orders','orders.id','=','order_items.order_id')->where('order_items.vendor_id',$vendorId);}
 public static function finances($vendorId,$start=null,$end=null){
  $q=self::orderItems($vendorId)->where('orders.status','!=','Cancelled');if($start)$q->whereDate('orders.created_at','>=',$start);if($end)$q->whereDate('orders.created_at','<=',$end);
  $rows=$q->select('order_items.*','orders.status as order_status')->get();$gross=$rows->sum(function($i){return $i->price*$i->quantity;});$discount=$rows->sum('vendor_discount');$commission=$rows->sum('vendor_commission');
  $refunds=DB::table('order_return_items')->join('order_items','order_items.id','=','order_return_items.order_item_id')->join('order_returns','order_returns.id','=','order_return_items.return_id')->where('order_items.vendor_id',$vendorId)->where('order_returns.refund_status','Test refund recorded');if($start)$refunds->whereDate('order_returns.updated_at','>=',$start);if($end)$refunds->whereDate('order_returns.updated_at','<=',$end);
  $refund=0;$merchandiseRefund=0;$commissionReversal=0;
  foreach($refunds->select('order_return_items.quantity','order_items.quantity as ordered_quantity','order_items.price','order_items.vendor_discount','order_items.vendor_commission','order_returns.refund_amount','order_returns.id')->get() as $r){
   $total=DB::table('order_return_items')->join('order_items','order_items.id','=','order_return_items.order_item_id')->where('return_id',$r->id)->sum(DB::raw('order_return_items.quantity*order_items.price'));
   $refund+=$total?(int)round($r->refund_amount*$r->quantity*$r->price/$total):0;
   $merchandiseRefund+=(int)round(($r->price*$r->ordered_quantity-$r->vendor_discount)*$r->quantity/$r->ordered_quantity);
   $commissionReversal+=(int)round($r->vendor_commission*$r->quantity/$r->ordered_quantity);
  }
  $settled=$rows->whereIn('order_status',['Delivered','Return requested','Return received','Partially returned'])->sum(function($i){return $i->price*$i->quantity-$i->vendor_discount-$i->vendor_commission;});
  $paid=self::orderItems($vendorId)->join('payments','payments.order_id','=','orders.id')->where('payments.status','Paid')->where('orders.status','Delivered')->sum(DB::raw('order_items.price*order_items.quantity-order_items.vendor_discount-order_items.vendor_commission'));
  return ['gross'=>$gross,'discount'=>$discount,'commission'=>$commission-$commissionReversal,'refund'=>$refund,'net'=>$gross-$discount-$merchandiseRefund-$commission+$commissionReversal,'pending'=>max(0,$settled-$merchandiseRefund+$commissionReversal),'available'=>max(0,$paid-$merchandiseRefund+$commissionReversal),'count'=>$rows->pluck('order_id')->unique()->count()];
 }
 public static function receivePurchase($inward,$lines,$purchaseId){
  if(!$purchaseId)return;$po=\App\PurchaseOrder::where('id',$purchaseId)->lockForUpdate()->firstOrFail();if(!in_array($po->status,['Placed','Partially Received'],true)||$po->supplier_status==='Rejected')Commerce::error('This purchase order cannot receive goods.');
  foreach(collect($lines)->groupBy('product_id') as $productId=>$group){$item=$po->items()->where('product_id',$productId)->lockForUpdate()->first();$quantity=$group->sum('quantity');if(!$item||$quantity>$item->pending_quantity)Commerce::error('Received quantity exceeds the pending purchase quantity.');$item->increment('received_quantity',$quantity);}
  $remaining=$po->items()->get()->sum('pending_quantity');$po->update(['status'=>$remaining?'Partially Received':'Received']);$inward->update(['purchase_order_id'=>$po->id,'purchase_order_no'=>$po->number,'supplier'=>$po->vendor->name]);self::notify($po->vendor,'Goods received against '.$po->number,['purchase_order_id'=>$po->id]);
 }
}
