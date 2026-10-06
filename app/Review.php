<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Review extends Model {protected $guarded=['id'];protected $casts=['approved'=>'boolean'];public function user(){return $this->belongsTo(User::class);}public function product(){return $this->belongsTo(Product::class);}}
