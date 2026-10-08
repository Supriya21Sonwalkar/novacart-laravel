<?php
namespace App\Services;
use App\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class ReturnFlow {
 public static function request(Order $order,array $input,$admin=false){return DB::transaction(function()use($order,$input,$admin){
  $o=Order::where('id',$order->id)->lockForUpdate()->firstOrFail();
  $existing=DB::table('order_returns')->where('request_key',$input['key'])->first();if($existing){if((int)$existing->order_id!==(int)$o->id)abort(403);return $existing->id;}
  if(!in_array($o->status,['Delivered','Return requested','Return received','Partially returned'],true))Commerce::error('Returns are available only after delivery.');
  if($o->stock_restored&&!DB::table('order_returns')->where('order_id',$o->id)->exists())Commerce::error('This historic order already had its goods returned. A second return cannot restore stock again.');
  $delivered=collect($o->timeline)->last(function($e){return $e['status']==='Delivered';});if(!$admin&&(!$delivered||\Carbon\Carbon::parse($delivered['date'])->lt(now()->subDays(7))))Commerce::error('The 7-day return window has closed.');
  $items=$o->items()->get()->keyBy('id');$lines=[];$refund=0;
  foreach($input['quantities'] as $id=>$quantity){$qty=(int)$quantity;if(!$qty)continue;$item=$items->get($id);if(!$item)Commerce::error('Choose products from this order.');$used=DB::table('order_return_items')->join('order_returns','order_returns.id','=','order_return_items.return_id')->where('order_item_id',$id)->where('order_returns.status','!=','Rejected')->sum('quantity');if($qty<1||$qty>$item->quantity-$used)Commerce::error('Return quantity exceeds the remaining quantity for '.$item->name.'.');$lines[]=['order_item_id'=>$id,'quantity'=>$qty];$refund+=$qty*$item->price;}
  if(!$lines)Commerce::error('Select at least one item and return quantity.');
  // Allocate the original discount and tax; delivery charges are not refunded here.
  $amount=$o->subtotal?(int)round($refund*($o->subtotal-$o->discount+$o->tax)/$o->subtotal):0;
  $cap=max(0,$o->subtotal-$o->discount+$o->tax-(int)DB::table('order_returns')->where('order_id',$o->id)->where('status','!=','Rejected')->sum('refund_amount'));$amount=min($amount,$cap);
  $id=DB::table('order_returns')->insertGetId(['number'=>'RET-'.strtoupper(Str::random(12)),'order_id'=>$o->id,'request_key'=>$input['key'],'reason'=>$input['reason'],'refund_amount'=>$amount,'created_at'=>now(),'updated_at'=>now()]);
  foreach($lines as $line)DB::table('order_return_items')->insert($line+['return_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
  self::timeline($o,'Return requested');Commerce::notify($o,'Return request submitted');Commerce::log('Return requested',['return_id'=>$id,'order'=>$o->number]);return $id;
 },3);}
 private static function timeline($o,$status){$t=$o->timeline?:[];$t[]=['status'=>$status,'date'=>now()->toIso8601String()];$o->update(['status'=>$status,'timeline'=>$t]);}
 public static function action($id,$action,array $input=[]){return DB::transaction(function()use($id,$action,$input){
  // Use the same order-first lock order as cancellation and return requests.
  $ref=DB::table('order_returns')->where('id',$id)->first();abort_unless($ref,404);$o=Order::where('id',$ref->order_id)->lockForUpdate()->firstOrFail();$r=DB::table('order_returns')->where('id',$id)->lockForUpdate()->first();
  $values=['updated_at'=>now(),'processed_by'=>auth()->id()];
  if($action==='approve'||$action==='reject'){if($r->status!=='Requested')Commerce::error('This request has already been reviewed.');$values['status']=$action==='approve'?'Approved':'Rejected';if($action==='reject'){$values['refund_status']='Not applicable';$values['inspection_notes']=$input['notes']??null;}}
  elseif($action==='receive'){
   if($r->status!=='Approved')Commerce::error('Approve this request before receiving goods.');$lines=DB::table('order_return_items')->where('return_id',$id)->orderBy('order_item_id')->get();
   foreach($lines as $line){if(!array_key_exists($line->id,$input['restock']??[]))Commerce::error('Record the usable quantity for every returned item.');$usable=(int)$input['restock'][$line->id];if($usable<0||$usable>$line->quantity)Commerce::error('Usable quantity must be between zero and received quantity.');$item=$o->items()->findOrFail($line->order_item_id);BatchStock::restore($item,$line->quantity,$usable,'Return '.$r->number);DB::table('order_return_items')->where('id',$line->id)->update(['restock_quantity'=>$usable,'updated_at'=>now()]);}
   $values['status']='Received';$values['inspection_notes']=$input['notes'];
   $received=(int)DB::table('order_return_items')->join('order_returns','order_returns.id','=','order_return_items.return_id')->where('order_returns.order_id',$o->id)->where('order_returns.status','Received')->sum('quantity')+$lines->sum('quantity');
   $full=$received===$o->items->sum('quantity');self::timeline($o,$full?'Return received':'Partially returned');$o->update(['stock_restored'=>$full]);
   if($full){$out=\App\Outward::where('order_id',$o->id)->where('status','Delivered')->first();if($out)OutwardFlow::event($out,'Returned','All returned goods inspected.');}
   Commerce::notify($o,'Returned goods received and inspected');
  }elseif($action==='refund'){
   if($r->status!=='Received'||$r->refund_status!=='Pending')Commerce::error('Only inspected, unrefunded returns can be refunded.');$values['refund_status']='Test refund recorded';DB::table('payments')->where('order_id',$o->id)->update(['status'=>'Test return refund recorded — no money moved','updated_at'=>now()]);Commerce::notify($o,'Test refund recorded for '.$r->number.'; no money moved');
  }else Commerce::error('Unknown return action.');
  DB::table('order_returns')->where('id',$id)->update($values);
  if($action==='reject'&&!DB::table('order_returns')->where('order_id',$o->id)->whereIn('status',['Requested','Approved'])->exists()&&$o->status==='Return requested')$o->update(['status'=>'Delivered']);
  Commerce::log('Return '.$action,['number'=>$r->number,'order'=>$o->number]);return $id;
 },3);}
 public static function fullReceive($order,$condition,$reason){$q=[];foreach($order->items as $i){$used=DB::table('order_return_items')->join('order_returns','order_returns.id','=','order_return_items.return_id')->where('order_item_id',$i->id)->where('order_returns.status','!=','Rejected')->sum('quantity');$q[$i->id]=max(0,$i->quantity-$used);} $id=self::request($order,['key'=>(string)Str::uuid(),'quantities'=>$q,'reason'=>$reason],true);self::action($id,'approve');$restock=[];foreach(DB::table('order_return_items')->where('return_id',$id)->get() as $line)$restock[$line->id]=$condition==='restock'?$line->quantity:0;self::action($id,'receive',['restock'=>$restock,'notes'=>$reason]);return $id;}
}
