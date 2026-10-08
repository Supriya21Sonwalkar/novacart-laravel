<?php
namespace App\Services;
use App\Outward;
use App\Order;
use App\Product;
use App\User;
use App\StoreRecord;
use Illuminate\Support\Facades\DB;
class OutwardFlow {
 public static function event($o,$status,$note=''){$t=$o->timeline?:[];$t[]=['status'=>$status,'note'=>$note,'by'=>auth()->user()->name,'at'=>now()->toIso8601String()];$o->timeline=$t;$o->status=$status;$o->save();Commerce::log('Outward '.$status,['number'=>$o->number]);}
 public static function order($o){$order=Order::where('id',$o->order_id)->lockForUpdate()->firstOrFail();if(!in_array($order->status,['Placed','Confirmed','Packing','Packed'],true)||$order->stock_restored)Commerce::error('This order is no longer available for dispatch.');return $order;}
 public static function transition(Outward $entry,$action,$input=[]){return DB::transaction(function()use($entry,$action,$input){
  $o=Outward::where('id',$entry->id)->lockForUpdate()->firstOrFail();$o->load('items');if($o->type==='Order Dispatch')abort_unless(auth()->user()->canManage('orders'),403);
  if($action==='confirm'){
   if($o->status!=='Draft')Commerce::error('Only drafts can be confirmed.');
   if(!$o->items->count())Commerce::error('Add at least one product.');
   if($o->type==='Order Dispatch'){
    $order=self::order($o);$original=$order->items()->get()->keyBy('id');
    if($o->items->count()!==$original->count())Commerce::error('The order items changed. Recreate the draft.');
    foreach($o->items as $i){$line=$original->get($i->order_item_id);$p=Product::withTrashed()->where('id',$i->product_id)->lockForUpdate()->first();if(!$line||$line->quantity!=$i->quantity||!$p||!$p->active||$p->trashed())Commerce::error('An order product is unavailable or expired.');}
    BatchStock::dispatchCheck($order,today()->toDateString());
   }else{
    foreach($o->items->sortBy('product_id') as $i){$p=Product::where('id',$i->product_id)->lockForUpdate()->first();if(!$p||$p->stock<$i->quantity)Commerce::error('Insufficient stock for '.$i->name.'.');
     if($o->type!=='Internal Transfer'){BatchStock::consume($p,$i->quantity,['outward_item_id'=>$i->id],$o->type==='Expired Stock'?'expired':($o->type==='Damaged Stock'?'all':'usable'));DB::table('inventory')->insert(['product_id'=>$p->id,'adjustment'=>-$i->quantity,'reason'=>$o->number.' '.$o->type,'user_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);}
    }
    $o->stock_applied=$o->type!=='Internal Transfer';
   }
   $o->confirmed_by=auth()->id();$o->confirmed_at=now();if($o->type!=='Order Dispatch')$o->completed_at=now();self::event($o,$o->type==='Order Dispatch'?'Pending':'Completed','Confirmed stock movement.');
  }elseif($action==='pack'){
   if($o->status!=='Pending'||$o->type!=='Order Dispatch')Commerce::error('Only pending dispatches can be packed.');self::order($o);self::event($o,'Packed');
  }elseif($action==='dispatch'){
   if($o->status!=='Packed')Commerce::error('Pack this outward before dispatch.');$order=self::order($o);
   foreach($o->items->sortBy('product_id') as $i){$p=Product::withTrashed()->where('id',$i->product_id)->lockForUpdate()->first();$expected=$o->expected_at?\Carbon\Carbon::parse($o->expected_at)->toDateString():today()->toDateString();if(!$p||!$p->active||$p->trashed())Commerce::error('A dispatch product is unavailable or expires before delivery.');}
   BatchStock::dispatchCheck($order,$o->expected_at?\Carbon\Carbon::parse($o->expected_at)->toDateString():today()->toDateString());
   if(!$o->partner||(!$o->tracking_number&&!$o->rider_id))Commerce::error('Add a carrier and tracking number or assign a rider first.');
   if($o->rider_id){$u=User::where('id',$o->rider_id)->lockForUpdate()->firstOrFail();if(!$u->active||optional(StoreRecord::in('roles')->find($u->role_id))->name!=='delivery partner')Commerce::error('Choose an active delivery partner.');if(DB::table('deliveries')->join('orders','orders.id','=','deliveries.order_id')->where('rider_id',$u->id)->where('order_id','!=',$order->id)->whereNotIn('orders.status',['Delivered','Cancelled','Refunded','Return requested'])->exists())Commerce::error('This rider already has an active delivery.');$d=DB::table('deliveries')->where('order_id',$order->id)->first();if(!$d)Commerce::error('This order has no delivery schedule. Use carrier tracking or configure its delivery first.');DB::table('deliveries')->where('id',$d->id)->update(['rider_id'=>$u->id,'updated_at'=>now()]);}
   Commerce::status($order,'Shipped',true);DB::table('shipments')->where('order_id',$order->id)->update(['tracking_number'=>$o->tracking_number,'method'=>$o->partner,'updated_at'=>now()]);$o->dispatched_at=now();self::event($o,'Dispatched');
  }elseif($action==='deliver'){
   if($o->status!=='Dispatched')Commerce::error('Only dispatched orders can be delivered.');$order=Order::where('id',$o->order_id)->lockForUpdate()->firstOrFail();if(!in_array($order->status,['Shipped','Picked up','On the way','Delivered'],true))Commerce::error('The order is closed or no longer in delivery.');if($order->status!=='Delivered')Commerce::status($order,'Delivered',true);$o->refresh();if($o->status!=='Delivered'){$o->completed_at=now();self::event($o,'Delivered');}
  }elseif($action==='return'){
 if($o->status!=='Delivered')Commerce::error('Only delivered goods can be returned.');$order=Order::where('id',$o->order_id)->lockForUpdate()->firstOrFail();ReturnFlow::fullReceive($order,$input['return_condition'],$input['return_reason']);$o->refresh();
}elseif($action==='cancel'){
   if(!in_array($o->status,['Draft','Pending','Packed'],true))Commerce::error('Only un-dispatched entries can be cancelled.');$o->active_order_id=null;self::event($o,'Cancelled','Order remains unchanged; cancelling this entry does not cancel the customer order.');
  }else Commerce::error('Unknown outward action.');
  return $o;
 },3);}
}
