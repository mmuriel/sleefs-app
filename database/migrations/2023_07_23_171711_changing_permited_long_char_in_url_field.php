<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE `product_images` CHANGE `url` `url` VARCHAR(256)");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE `product_images` CHANGE `url` `url` VARCHAR(200)");
        });
    }
};
