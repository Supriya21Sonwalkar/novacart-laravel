<?php
namespace App\Services;
use App\Order;
use Illuminate\Support\Facades\DB;
class StoreReporting {
 public static function calculate($from,$to){
  $orders=Order::whereDate('created_at','>=',$from)->whereDate('created_at','<=',$to)->whereIn('status',['Delivered','Return requested','Return received','Partially returned','Refunded'])->get();
  $sales=0;$cost=0;$unknown=0;$refund=0;
  foreach($orders as $order){
   $sales+=$order->subtotal-$order->discount;
   foreach($order->items as $item){$allocations=DB::table('batch_allocations')->where('order_item_id',$item->id)->get();$allocated=0;foreach($allocations as $a){$allocated+=$a->quantity;$used=$a->quantity-$a->restored_quantity;if($a->unit_cost===null)$unknown+=$used;else $cost+=$used*$a->unit_cost;}$unknown+=max(0,$item->quantity-$allocated);}
   // Refund entries include allocated tax. Calculate merchandise separately so tax is not counted as profit.
   $returnedValue=0;foreach(DB::table('order_return_items')->join('order_returns','order_returns.id','=','order_return_items.return_id')->join('order_items','order_items.id','=','order_return_items.order_item_id')->where('order_returns.order_id',$order->id)->where('order_returns.status','Received')->select('order_return_items.quantity','order_items.price')->get() as $r)$returnedValue+=$r->quantity*$r->price;$refund+=$order->subtotal?min($order->subtotal-$order->discount,(int)round($returnedValue*($order->subtotal-$order->discount)/$order->subtotal)):0;
  }
  $expenses=DB::table('store_expenses')->whereBetween('spent_on',[$from,$to])->sum('amount');
  return ['delivered_orders'=>$orders->count(),'merchandise'=>$sales,'merchandise_refunds'=>$refund,'known_cost'=>$cost,'unknown_units'=>$unknown,'expenses'=>$expenses,'margin'=>$unknown?null:$sales-$refund-$cost,'result'=>$unknown?null:$sales-$refund-$cost-$expenses];
 }
}
