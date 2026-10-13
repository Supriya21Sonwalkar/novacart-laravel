<?php
namespace App;
class Category extends StoreRecord {
 protected $table='categories';

 public function subcategories()
 {
  return $this->hasMany(Subcategory::class);
 }
}
