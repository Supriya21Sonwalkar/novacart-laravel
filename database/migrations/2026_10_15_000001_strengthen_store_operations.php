<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class StrengthenStoreOperations extends Migration {
 public function up(){
  Schema::table('payments',function(Blueprint $t){$t->string('state',30)->default('Demo');$t->string('reference',100)->nullable()->unique();$t->timestamp('expires_at')->nullable();$t->timestamp('confirmed_at')->nullable();});
  Schema::table('deliveries',function(Blueprint $t){$t->string('proof',500)->nullable();$t->string('failure_reason',500)->nullable();$t->timestamp('failed_at')->nullable();});
  Schema::create('store_expenses',function(Blueprint $t){$t->bigIncrements('id');$t->date('spent_on');$t->string('description',250);$t->unsignedBigInteger('amount');$t->unsignedBigInteger('created_by');$t->timestamps();});
  Schema::create('store_mail_outbox',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('notification_id')->unique();$t->string('recipient');$t->string('subject',250);$t->text('body');$t->string('status',20)->default('Pending');$t->unsignedInteger('attempts')->default(0);$t->timestamp('retry_at')->nullable();$t->timestamp('sent_at')->nullable();$t->text('last_error')->nullable();$t->timestamps();});
 }
 public function down(){Schema::dropIfExists('store_mail_outbox');Schema::dropIfExists('store_expenses');Schema::table('deliveries',function(Blueprint $t){$t->dropColumn(['proof','failure_reason','failed_at']);});Schema::table('payments',function(Blueprint $t){$t->dropColumn(['state','reference','expires_at','confirmed_at']);});}
}
