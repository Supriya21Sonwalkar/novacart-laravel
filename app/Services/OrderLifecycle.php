<?php
namespace App\Services;

use App\Order;
use App\Outward;
use Illuminate\Support\Facades\DB;

class OrderLifecycle
{
 public static function validate(Order $order,$next,$metadata=[]){
  $allowed=[
   'Placed'=>['Confirmed','Cancelled'],
   'Confirmed'=>['Packed','Cancelled'],
   'Packing'=>['Packed','Cancelled'],
   'Packed'=>['Shipped','Cancelled'],
   'Shipped'=>['Picked up','Delivered'],
   'Picked up'=>['On the way'],
   'On the way'=>['Delivered'],
   'Delivered'=>['Refunded'],
   'Return received'=>['Refunded'],
   'Partially returned'=>['Refunded'],
  ];
  if(!in_array($next,$allowed[$order->status]??[],true))Commerce::error('This order cannot move from '.$order->status.' to '.$next.'. Follow the preparation and dispatch workflow.');
  if($next==='Packed'&&($metadata['outward_action']??null)!=='pack')Commerce::error('Mark the outward packed in the order workspace.');
  if($next==='Shipped'){
   $out=Outward::where('active_order_id',$order->id)->where('type','Order Dispatch')->where('status','Packed')->first();
   if(!$out||!$out->partner||(!$out->tracking_number&&!$out->rider_id))Commerce::error('Create the outward, pack it and save dispatch details before shipping.');
   self::requirePayment($order);
  }
  if($next==='Delivered'&&strlen(trim($metadata['delivery_proof']??''))<5)Commerce::error('Provide delivery proof before completing this order.');if(in_array($next,['Picked up','On the way','Delivered'],true)&&!Outward::where('order_id',$order->id)->whereIn('status',['Dispatched','Delivered'])->exists())Commerce::error('Confirm outward dispatch before recording delivery progress.');
 }
 public static function requirePayment(Order $order){
  $payment=DB::table('payments')->where('order_id',$order->id)->first();
  if($payment&&in_array($payment->state,['Pending','Failed','Expired'],true))Commerce::error('The online payment is not confirmed. Do not dispatch this order.');
 }
}
