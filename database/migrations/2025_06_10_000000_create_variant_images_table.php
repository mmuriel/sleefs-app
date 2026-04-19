<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVariantImagesTable extends Migration
{
    public function up()
    {
        Schema::create('variant_images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('idsp',50)->index();
            $table->unsignedInteger('idvariant')->index();
            $table->string('url',256);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('variant_images');
    }
}