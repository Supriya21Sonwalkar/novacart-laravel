<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class AddBatchInventoryAndReturns extends Migration {
 public function up(){
  if(!Schema::hasTable('product_batches'))Schema::create('product_batches',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('product_id')->index();$t->unsignedBigInteger('inward_item_id')->nullable()->unique();$t->string('batch_number',80);$t->string('supplier_invoice_no',120)->nullable()->index();$t->dateTime('received_at')->index();$t->date('manufactured_on')->nullable();$t->date('expires_on')->nullable()->index();$t->unsignedInteger('received_quantity');$t->unsignedInteger('remaining_quantity');$t->unsignedBigInteger('unit_cost')->nullable();$t->string('source',40);$t->timestamps();$t->unique(['product_id','batch_number']);});
  if(!Schema::hasTable('batch_allocations'))Schema::create('batch_allocations',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('batch_id')->index();$t->unsignedBigInteger('order_item_id')->nullable()->index();$t->unsignedBigInteger('outward_item_id')->nullable()->index();$t->unsignedInteger('quantity');$t->unsignedInteger('returned_quantity')->default(0);$t->unsignedInteger('restored_quantity')->default(0);$t->unsignedBigInteger('unit_cost')->nullable();$t->timestamps();});
  if(!Schema::hasColumn('orders','invoice_number'))Schema::table('orders',function(Blueprint $t){$t->string('invoice_number',80)->nullable()->unique();});
  if(DB::connection()->getDriverName()==='mysql' && !DB::select("SHOW INDEX FROM orders WHERE Key_name='orders_invoice_number_unique'"))Schema::table('orders',function(Blueprint $t){$t->unique('invoice_number');});
  if(!Schema::hasColumn('inward_items','batch_number'))Schema::table('inward_items',function(Blueprint $t){$t->string('batch_number',80)->nullable();});
  foreach(['manufactured_on','expires_on'] as $column)if(!Schema::hasColumn('inward_items',$column))Schema::table('inward_items',function(Blueprint $t)use($column){$t->date($column)->nullable();});
  if(!Schema::hasTable('order_returns'))Schema::create('order_returns',function(Blueprint $t){$t->bigIncrements('id');$t->string('number',80)->unique();$t->unsignedBigInteger('order_id')->index();$t->uuid('request_key')->unique();$t->string('status',30)->default('Requested');$t->string('reason',1000);$t->text('inspection_notes')->nullable();$t->unsignedBigInteger('refund_amount')->default(0);$t->string('refund_status',40)->default('Pending');$t->unsignedBigInteger('processed_by')->nullable();$t->timestamps();});
  if(!Schema::hasTable('order_return_items'))Schema::create('order_return_items',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('return_id')->index();$t->unsignedBigInteger('order_item_id')->index();$t->unsignedInteger('quantity');$t->unsignedInteger('restock_quantity')->default(0);$t->timestamps();$t->unique(['return_id','order_item_id']);});
  // Existing stock was already received. Preserve it without inventing a purchase cost.
  DB::table('products')->orderBy('id')->chunk(200,function($rows){foreach($rows as $p)if($p->stock>0 && !DB::table('product_batches')->where('product_id',$p->id)->exists())DB::table('product_batches')->insert(['product_id'=>$p->id,'batch_number'=>'OPEN-'.$p->id,'received_at'=>$p->created_at?:now(),'expires_on'=>$p->expires_on,'received_quantity'=>$p->stock,'remaining_quantity'=>$p->stock,'source'=>'Opening balance','created_at'=>now(),'updated_at'=>now()]);});
  DB::table('orders')->orderBy('id')->chunk(200,function($rows){foreach($rows as $o)DB::table('orders')->where('id',$o->id)->whereNull('invoice_number')->update(['invoice_number'=>'INV-'.str_pad($o->id,8,'0',STR_PAD_LEFT)]);});
 }
 public function down(){Schema::dropIfExists('order_return_items');Schema::dropIfExists('order_returns');Schema::table('inward_items',function(Blueprint $t){$t->dropColumn(['batch_number','manufactured_on','expires_on']);});Schema::table('orders',function(Blueprint $t){$t->dropColumn('invoice_number');});Schema::dropIfExists('batch_allocations');Schema::dropIfExists('product_batches');}
}
