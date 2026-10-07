<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class PaymentPreference extends Migration {public function up(){Schema::table('users',function(Blueprint $t){$t->string('payment_preference')->nullable();});}public function down(){Schema::table('users',function(Blueprint $t){$t->dropColumn('payment_preference');});}}
