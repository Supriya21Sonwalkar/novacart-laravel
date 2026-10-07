<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInwardTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('inwards', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('number')->unique();

            $table->date('inward_date')->index();

            $table->string('inward_type')->index();

            $table->string('warehouse');

            $table->string('supplier');

            $table->string('supplier_contact')->nullable();

            $table->string('supplier_invoice_no')->nullable();

            $table->date('invoice_date')->nullable();

            $table->string('purchase_order_no')->nullable();

            $table->string('delivery_challan_no')->nullable();

            $table->string('received_by');

            $table->text('notes')->nullable();

            /*
             * Money values are stored as integer values.
             */
            $table->unsignedBigInteger('subtotal')->default(0);

            $table->unsignedBigInteger('tax')->default(0);

            $table->unsignedBigInteger('other_charges')->default(0);

            $table->unsignedBigInteger('total')->default(0);

            $table->string('status')
                ->default('Received')
                ->index();

            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->index();

            $table->timestamps();
        });

        Schema::create('inward_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('inward_id')
                ->index();

            /*
             * We intentionally do NOT add a database-level
             * foreign key to products yet.
             *
             * This keeps the migration independent of the
             * existing NovaCart database structure.
             */
            $table->unsignedBigInteger('product_id')
                ->index();

            $table->string('sku');

            $table->string('product_name');

            $table->unsignedInteger('ordered_qty')
                ->default(0);

            $table->unsignedInteger('received_qty');

            $table->unsignedBigInteger('unit_cost')
                ->default(0);

            $table->decimal('gst_percent', 5, 2)
                ->default(0);

            $table->unsignedBigInteger('other_charges')
                ->default(0);

            $table->unsignedBigInteger('subtotal')
                ->default(0);

            $table->unsignedBigInteger('tax')
                ->default(0);

            $table->unsignedBigInteger('total')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('inward_items');

        Schema::dropIfExists('inwards');
    }
}