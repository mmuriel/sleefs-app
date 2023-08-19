<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('idsp',60)->unique();
            $table->string('title',150)->index();
            $table->string('vendor',100)->index();
            $table->string('product_type',40)->index();
            $table->string('handle',100);
            $table->enum("delete_status",[1,2,3,4,5])->default(1)->comment("Este valor define el borrado logico de un producto, recibe los siguientes valores posibles: 1. Ok; 2. Borrado en shopify; 3. Borrado en shiphero; 4. Borrado en todos; 5. Aprobado para borrado asincrónico.")->index();
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
        Schema::dropIfExists('products');
    }
}
