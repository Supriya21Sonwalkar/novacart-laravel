<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class StoreRecord extends Model {
 protected $guarded=['id'];
 protected $casts=['data'=>'array','active'=>'boolean','read'=>'boolean'];
 public static function in($table){$m=new static;$m->setTable($table);return $m->newQuery();}
}
