
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

        // Preserve existing zones and provide a temporary value
        // for zones that were created before the city field existed.
        DB::table('delivery_zones')
            ->whereNull('city')
            ->update(['city' => 'Pune']);
    }

    public function down()
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
}
