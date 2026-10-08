<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class NormalizeLegacyInwardMoney extends Migration {
 public function up(){
  if(!Schema::hasColumn('inwards','legacy_money_normalized'))Schema::table('inwards',function(Blueprint $t){$t->boolean('legacy_money_normalized')->default(false);});
  DB::transaction(function(){
   // The old import stored whole rupees. New receipts have batch links and store paise.
   $ids=DB::table('inwards')->where('legacy_money_normalized',false)->whereExists(function($q){$q->select(DB::raw(1))->from('inward_items')->whereColumn('inward_items.inward_id','inwards.id');})->whereNotExists(function($q){$q->select(DB::raw(1))->from('inward_items')->join('product_batches','product_batches.inward_item_id','=','inward_items.id')->whereColumn('inward_items.inward_id','inwards.id');})->pluck('id');
   foreach($ids as $id){$values=[];foreach(['subtotal','tax','other_charges','total'] as $field)$values[$field]=DB::raw($field.' * 100');$values['legacy_money_normalized']=true;DB::table('inwards')->where('id',$id)->update($values);unset($values['legacy_money_normalized']);$values['unit_cost']=DB::raw('unit_cost * 100');DB::table('inward_items')->where('inward_id',$id)->update($values);}
  });
 }
 public function down(){DB::transaction(function(){foreach(DB::table('inwards')->where('legacy_money_normalized',true)->pluck('id') as $id){$values=[];foreach(['subtotal','tax','other_charges','total'] as $field)$values[$field]=DB::raw($field.' / 100');DB::table('inwards')->where('id',$id)->update($values);$values['unit_cost']=DB::raw('unit_cost / 100');DB::table('inward_items')->where('inward_id',$id)->update($values);}});Schema::table('inwards',function(Blueprint $t){$t->dropColumn('legacy_money_normalized');});}
}
