<?php
namespace App\Services;

use App\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentFlow
{
 const SANDBOX='Online payment (sandbox)';
 public static function enabled(){return app()->environment('local','testing')&&config('store_management.payment_sandbox',true);}
 public static function methods(){$methods=array_values(array_filter(array_map('trim',explode(',',Commerce::settings()['payment_methods']??'Test card / UPI,Cash on delivery (test)'))));if(self::enabled())$methods[]=self::SANDBOX;else $methods=array_values(array_diff($methods,[self::SANDBOX]));return array_values(array_unique($methods));}
 public static function start(Order $order){
  $pending=$order->payment_method===self::SANDBOX;
  DB::table('payments')->insert(['order_id'=>$order->id,'method'=>$order->payment_method,'amount'=>$order->total,'state'=>$pending?'Pending':(stripos($order->payment_method,'Cash on delivery')!==false?'COD pending':'Demo'),'reference'=>(string)Str::uuid(),'expires_at'=>$pending?now()->addMinutes(15):null,'status'=>$pending?'Sandbox payment pending — stock reserved for 15 minutes':'Test — no payment collected','created_at'=>now(),'updated_at'=>now()]);
 }
 public static function settle(Order $order,$outcome){
  return DB::transaction(function()use($order,$outcome){
   $o=Order::where('id',$order->id)->lockForUpdate()->firstOrFail();$p=DB::table('payments')->where('order_id',$o->id)->lockForUpdate()->first();
   if(!$p||$p->method!==self::SANDBOX)Commerce::error('This order does not have a sandbox payment.');
   if(!in_array($outcome,['Paid','Failed','Expired'],true))Commerce::error('Invalid payment outcome.');
   if($p->state===$outcome)return $o;
   if($p->state!=='Pending'||$o->status!=='Placed')Commerce::error('This payment has already been resolved.');
   if($p->expires_at&&\Carbon\Carbon::parse($p->expires_at)->lte(now()))$outcome='Expired';
   if($outcome!=='Paid')Commerce::status($o,'Cancelled',true);
   DB::table('payments')->where('id',$p->id)->update(['state'=>$outcome,'status'=>'Sandbox '.$outcome.' — no money moved','confirmed_at'=>$outcome==='Paid'?now():null,'updated_at'=>now()]);
   Commerce::notify($o,'Sandbox payment '.$outcome.' for '.$o->number);
   return $o->fresh();
  },3);
 }
 public static function expire(){ $count=0;foreach(DB::table('payments')->where('state','Pending')->where('method',self::SANDBOX)->where('expires_at','<=',now())->pluck('order_id') as $id){try{self::settle(Order::findOrFail($id),'Expired');$count++;}catch(\Illuminate\Validation\ValidationException $e){/* Another worker already resolved this order. */}}return $count; }
}
