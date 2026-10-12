<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Vendor extends Model {
 protected $guarded=['id'];
 protected $casts=['active'=>'boolean','data'=>'array'];
 public function purchases(){return $this->hasMany(PurchaseOrder::class);}
}
