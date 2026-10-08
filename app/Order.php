<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {protected $guarded=['id'];protected $casts=['timeline'=>'array','stock_restored'=>'boolean','user_id'=>'integer'];public function items(){return $this->hasMany(OrderItem::class);}public function getAddressAttribute(){return json_decode(\DB::table('order_addresses')->where('order_id',$this->id)->value('data'),true)?:[];}}
