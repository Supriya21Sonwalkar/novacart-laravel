<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class DeliveryAndCatalog extends Migration {
 public function up(){
  Schema::table('products',function(Blueprint $t){$t->string('delivery_type')->default('standard');$t->unsignedInteger('delivery_minutes')->default(60);$t->string('unit')->nullable();$t->date('expires_on')->nullable();$t->boolean('cold_storage')->default(false);});
  Schema::create('delivery_zones',function(Blueprint $t){$t->bigIncrements('id');$t->string('name');$t->text('pincodes');$t->time('opens_at')->default('08:00');$t->time('closes_at')->default('22:00');$t->unsignedInteger('travel_minutes')->default(30);$t->boolean('express')->default(false);$t->boolean('active')->default(true);$t->timestamps();});
  Schema::create('delivery_slots',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('zone_id');$t->dateTime('starts_at');$t->dateTime('ends_at');$t->unsignedInteger('capacity')->default(10);$t->timestamps();});
  Schema::create('deliveries',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('order_id')->unique();$t->unsignedBigInteger('zone_id')->nullable();$t->unsignedBigInteger('slot_id')->nullable();$t->unsignedBigInteger('rider_id')->nullable();$t->dateTime('promised_from');$t->dateTime('promised_to');$t->decimal('latitude',10,7)->nullable();$t->decimal('longitude',10,7)->nullable();$t->dateTime('location_at')->nullable();$t->dateTime('eta_at')->nullable();$t->timestamps();$t->index(['slot_id','rider_id']);});
  DB::table('roles')->insert(['name'=>'delivery partner','active'=>true,'data'=>json_encode(['permissions'=>[]]),'created_at'=>now(),'updated_at'=>now()]);
 }
 public function down(){Schema::dropIfExists('deliveries');Schema::dropIfExists('delivery_slots');Schema::dropIfExists('delivery_zones');Schema::table('products',function(Blueprint $t){$t->dropColumn(['delivery_type','delivery_minutes','unit','expires_on','cold_storage']);});DB::table('roles')->where('name','delivery partner')->delete();}
}
