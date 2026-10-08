<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Outward extends Model {
 protected $guarded=['id'];
 protected $casts=['timeline'=>'array','stock_applied'=>'boolean'];
 public function items(){return $this->hasMany(OutwardItem::class);}
 public function order(){return $this->belongsTo(Order::class);}
 public function creator(){return $this->belongsTo(User::class,'created_by');}
 public function getTotalAttribute(){return $this->items->sum(function($i){return $i->quantity*$i->unit_value;});}
}
