<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TablaShipheroPOItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sh_purchaseorder_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idpo')->index();
            $table->string('sku',150);
            $table->string('shid',150)->unique();
            $table->integer('quantity');
            $table->integer('quantity_received');
            $table->string('name',150);
            $table->string('idmd5',40);
            $table->timestamps();
            $table->string('product_type',150)->default('')->index();
            $table->integer('qty_pending')->default(0);
            $table->float('price', 8, 2)->default('0.00');
            $table->foreign('idpo')->references('id')->on('sh_purchaseorders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sh_purchaseorder_items');
    }
}
