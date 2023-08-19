<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TablaShipHeroPOUpdatesItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sh_purchaseorders_updates_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idpoupdate')->index();
            $table->unsignedInteger('idpoitem')->index();
            $table->integer('quantity');
            $table->integer('qty_before')->default(0);
            $table->string('sku',150);
            $table->string('position',180)->default('na');
            $table->integer('tries')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sh_purchaseorders_updates_items');
    }
}
