
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueTrackingNumberToOutwardsTable extends Migration
{
    public function up()
    {
        Schema::table('outwards', function (Blueprint $table) {
            $table->unique(
                'tracking_number',
                'outwards_tracking_number_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('outwards', function (Blueprint $table) {
            $table->dropUnique(
                'outwards_tracking_number_unique'
            );
        });
    }
}
