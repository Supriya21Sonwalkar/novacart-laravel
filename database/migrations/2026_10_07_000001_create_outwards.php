<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateOutwards extends Migration {
 public function up(){
  Schema::create('outwards',function(Blueprint $t){$t->bigIncrements('id');$t->string('number')->unique();$t->string('request_key')->unique();$t->unsignedBigInteger('order_id')->nullable()->index();$t->unsignedBigInteger('active_order_id')->nullable()->unique();$t->string('type');$t->string('status')->default('Draft')->index();$t->date('outward_date');$t->string('source')->default('Main inventory');$t->string('destination')->nullable();$t->text('reason')->nullable();$t->string('partner')->nullable();$t->string('tracking_number')->nullable();$t->unsignedBigInteger('rider_id')->nullable();$t->dateTime('expected_at')->nullable();$t->text('notes')->nullable();$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('confirmed_by')->nullable();$t->dateTime('confirmed_at')->nullable();$t->dateTime('dispatched_at')->nullable();$t->dateTime('completed_at')->nullable();$t->boolean('stock_applied')->default(false);$t->text('timeline');$t->timestamps();});
  Schema::create('outward_items',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('outward_id')->index();$t->unsignedBigInteger('product_id');$t->unsignedBigInteger('order_item_id')->nullable();$t->string('name');$t->string('sku');$t->string('variant')->nullable();$t->unsignedInteger('quantity');$t->unsignedInteger('unit_value');$t->date('expires_on')->nullable();$t->timestamps();});
 }
 public function down(){Schema::dropIfExists('outward_items');Schema::dropIfExists('outwards');}
}
