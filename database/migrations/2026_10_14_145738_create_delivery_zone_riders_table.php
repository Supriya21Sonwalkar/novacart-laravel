
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryZoneRidersTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_zone_riders', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('rider_id');
            $table->unsignedBigInteger('zone_id');

            $table->timestamps();

            // Prevent assigning the same zone to a rider twice.
            $table->unique(
                ['rider_id', 'zone_id'],
                'delivery_zone_riders_rider_zone_unique'
            );

            // Connect to existing users and delivery zones.
            $table->foreign('rider_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('zone_id')
                ->references('id')
                ->on('delivery_zones')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_zone_riders');
    }
}
