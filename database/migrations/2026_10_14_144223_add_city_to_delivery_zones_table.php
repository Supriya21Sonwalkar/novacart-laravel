<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddCityToDeliveryZonesTable extends Migration
{
    public function up()
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->string('city', 100)->nullable();
        });

        // Preserve unknown historical cities as null; the administrator supplies actual details.
    }

    public function down()
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
}
