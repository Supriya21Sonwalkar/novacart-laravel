<?php
namespace App\Services;
use App\Product;
use App\StoreRecord;
class Catalog {
 public static function descendants($id){$all=StoreRecord::in('categories')->where('active',true)->get();$ids=[(int)$id];do{$before=count($ids);foreach($all as $c)if(in_array((int)($c->data['parent_id']??0),$ids,true)&&!in_array($c->id,$ids,true))$ids[]=$c->id;}while(count($ids)>$before);return $ids;}
 public static function search($q,$term){$term=trim($term);if($term==='')return;$words=preg_split('/\s+/u',$term);foreach(array_slice($words,0,10) as $word){$q->where(function($q)use($word){$s='%'.$word.'%';$q->where('name','like',$s)->orWhere('tags','like',$s)->orWhere('sku','like',$s)->orWhere('description','like',$s);$categories=StoreRecord::in('categories')->where('active',true)->where('name','like',$s)->pluck('id');$ids=[];foreach($categories as $id)$ids=array_merge($ids,self::descendants($id));$q->orWhereIn('category_id',array_unique($ids))->orWhereIn('brand_id',StoreRecord::in('brands')->where('active',true)->where('name','like',$s)->pluck('id'));});}}
}
