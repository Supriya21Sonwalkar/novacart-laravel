<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model {
 use SoftDeletes;
 protected $guarded=['id'];
 protected $casts=['featured'=>'boolean','active'=>'boolean','price'=>'integer','stock'=>'integer'];
 public function getAvailableStockAttribute(){return \App\Services\BatchStock::available($this);}
 public function category(){return $this->belongsTo(Category::class);}
 public function brand(){return $this->belongsTo(Brand::class);}
 public function images(){return $this->hasMany(ProductImage::class)->orderBy('position');}
 public function reviews(){return $this->hasMany(Review::class);}
 public function getRatingAttribute(){return round($this->reviews()->where('approved',true)->avg('rating')?:0,1);}
 public function getOptionsAttribute(){return array_values(array_filter(array_map('trim',explode(',',$this->variants?:'Default'))));}
}
