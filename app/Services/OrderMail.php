<?php
namespace App\Services;
use App\Order;
use Illuminate\Support\Facades\DB;
class OrderMail {
 public static function enqueue(Order $order,$notification,$message){if(!filter_var($order->email,FILTER_VALIDATE_EMAIL))return;DB::table('store_mail_outbox')->insert(['notification_id'=>$notification->id,'recipient'=>$order->email,'subject'=>substr($message,0,250),'body'=>$message."\nOrder: ".$order->number."\nCheck your order in your NovaCart account. This is a local test notification.",'created_at'=>now(),'updated_at'=>now()]);}
 public static function process(){
  $sent=0;foreach(DB::table('store_mail_outbox')->where('status','Pending')->where('attempts','<',5)->where(function($q){$q->whereNull('retry_at')->orWhere('retry_at','<=',now());})->limit(50)->pluck('id') as $id){DB::transaction(function()use($id,&$sent){$row=DB::table('store_mail_outbox')->where('id',$id)->lockForUpdate()->first();if(!$row||$row->status!=='Pending')return;try{\Illuminate\Support\Facades\Mail::raw($row->body,function($mail)use($row){$mail->to($row->recipient)->subject($row->subject);});$logged=in_array(config('mail.default'),['log','array'],true);DB::table('store_mail_outbox')->where('id',$id)->update(['status'=>$logged?'Logged':'Sent','attempts'=>$row->attempts+1,'sent_at'=>now(),'last_error'=>null,'updated_at'=>now()]);$sent++;}catch(\Throwable $e){DB::table('store_mail_outbox')->where('id',$id)->update(['attempts'=>$row->attempts+1,'status'=>$row->attempts>=4?'Failed':'Pending','retry_at'=>now()->addMinutes(min(60,pow(2,$row->attempts))),'last_error'=>get_class($e),'updated_at'=>now()]);\Illuminate\Support\Facades\Log::warning('Order mail delivery failed',['outbox_id'=>$id,'exception'=>get_class($e)]);}}); }return $sent;
 }
}
