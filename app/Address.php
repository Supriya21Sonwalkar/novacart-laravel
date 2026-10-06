<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Address extends Model {protected $guarded=['id'];protected $casts=['is_default'=>'boolean'];}
