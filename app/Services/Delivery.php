<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class Delivery {
 public static function zone($pin){return DB::table('delivery_zones')->where('active',true)->get()->first(function($z)use($pin){return in_array($pin,array_map('trim',explode(',',$z->pincodes)),true);});}
 public static function options($items,$pin){
  $zone=self::zone($pin);if(!$zone)return [];
  $types=[];$minutes=0;$expiry=null;foreach($items as $i){$p=$i->product;if(!$p||!$p->active||$p->stock<1||($p->expires_on&&$p->expires_on<today()->toDateString()))return [];$types[]=$p->delivery_type;$minutes=max($minutes,$p->delivery_minutes);if($p->expires_on)$expiry=$expiry?min($expiry,$p->expires_on):$p->expires_on;}
  $dairy=in_array('dairy',$types,true);$fresh=$dairy||in_array('fresh',$types,true);$standard=in_array('standard',$types,true);$now=now();$open=Carbon::parse(today()->format('Y-m-d').' '.$zone->opens_at);$close=Carbon::parse(today()->format('Y-m-d').' '.$zone->closes_at);$options=[];
  $eta=$now->copy()->addMinutes($minutes);$available=self::availableRider();
  if((!$dairy||$minutes<=60)&&!$standard&&$zone->express&&$available&&$now->gte($open)&&$eta->lte($close)&&$minutes>=$zone->travel_minutes&&(!$expiry||$eta->toDateString()<=$expiry))$options[]=['value'=>'express','label'=>'Today within '.$minutes.' minutes','from'=>$now->toDateTimeString(),'to'=>$eta->toDateTimeString()];
  if($dairy)return $options;foreach(DB::table('delivery_slots')->where('zone_id',$zone->id)->where('starts_at','>',$now->copy()->addMinutes($minutes))->orderBy('starts_at')->limit(100)->get() as $s){$start=Carbon::parse($s->starts_at);$end=Carbon::parse($s->ends_at);if(!$start->isSameDay($end)||$start->format('H:i:s')<$zone->opens_at||$end->format('H:i:s')>$zone->closes_at)continue;if($fresh&&!$start->isToday())continue;if($expiry&&$end->toDateString()>$expiry)continue;if($standard&&$start->lt($now->copy()->addDay()))continue;$used=DB::table('deliveries')->join('orders','orders.id','=','deliveries.order_id')->where('slot_id',$s->id)->whereNotIn('orders.status',['Cancelled','Refunded'])->count();if($used<$s->capacity)$options[]=['value'=>(string)$s->id,'label'=>$start->format('D d M, H:i').' – '.$end->format('H:i'),'from'=>$s->starts_at,'to'=>$s->ends_at];}
  return $options;
 }
 public static function availableRider(){return DB::table('users')->join('roles','roles.id','=','users.role_id')->where('roles.name','delivery partner')->where('users.active',true)->whereNotIn('users.id',DB::table('deliveries')->join('orders','orders.id','=','deliveries.order_id')->whereNotIn('orders.status',['Delivered','Cancelled','Refunded'])->whereNotNull('rider_id')->select('rider_id'))->orderBy('users.id')->select('users.id')->first();}
 public static function reserve($order,$items,$address,$choice){
  $zone=self::zone($address->pincode);if(!$zone)Commerce::error('Delivery is unavailable at this pincode.');DB::table('delivery_zones')->where('id',$zone->id)->lockForUpdate()->first();
  $selected=collect(self::options($items,$address->pincode))->firstWhere('value',(string)$choice);if(!$selected)Commerce::error('Choose an available delivery time. Mixed fresh and standard products may need separate orders.');
  $rider=null;if($choice==='express'){$available=self::availableRider();if(!$available)Commerce::error('No express rider is available.');DB::table('users')->where('id',$available->id)->lockForUpdate()->first();if(DB::table('deliveries')->join('orders','orders.id','=','deliveries.order_id')->where('rider_id',$available->id)->whereNotIn('orders.status',['Delivered','Cancelled','Refunded'])->exists())Commerce::error('Express delivery is now full.');$rider=$available->id;}
  DB::table('deliveries')->insert(['order_id'=>$order->id,'zone_id'=>$zone->id,'slot_id'=>$choice==='express'?null:(int)$choice,'rider_id'=>$rider,'promised_from'=>$selected['from'],'promised_to'=>$selected['to'],'created_at'=>now(),'updated_at'=>now()]);
 }
}
