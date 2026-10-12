<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreatePurchaseOrders extends Migration {
 public function up(){
  Schema::create('vendors',function(Blueprint $t){$t->bigIncrements('id');$t->string('name');$t->boolean('active')->default(true);$t->text('data')->nullable();$t->timestamps();});
  Schema::create('purchase_orders',function(Blueprint $t){
   $t->bigIncrements('id');$t->string('number')->unique();$t->uuid('request_key')->unique();$t->unsignedBigInteger('vendor_id');$t->date('order_date');$t->string('status')->default('Placed')->index();$t->unsignedBigInteger('created_by');$t->text('notes')->nullable();$t->timestamps();
   $t->foreign('vendor_id')->references('id')->on('vendors');$t->foreign('created_by')->references('id')->on('users');
  });
  Schema::create('purchase_order_items',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('purchase_order_id');$t->unsignedBigInteger('product_id');$t->string('name');$t->string('sku');$t->string('category_name');$t->string('subcategory_name');$t->unsignedInteger('existing_stock');$t->unsignedInteger('quantity');$t->unsignedInteger('received_quantity')->default(0);$t->timestamps();
   $t->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');$t->foreign('product_id')->references('id')->on('products');$t->unique(['purchase_order_id','product_id']);
  });
 }
 public function down(){Schema::dropIfExists('purchase_order_items');Schema::dropIfExists('purchase_orders');Schema::dropIfExists('vendors');}
}
