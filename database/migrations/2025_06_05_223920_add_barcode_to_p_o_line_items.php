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
        Schema::table('sh_purchaseorder_items', function (Blueprint $table) {
            //
            $table->string('barcode',150)->nullable()->after('sku');

            //Genera un indice para barcode y otro para sku
            $table->index('barcode');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sh_purchaseorder_items', function (Blueprint $table) {

            //Elimina el indice barcode
            $table->dropIndex('barcode');

            //Elimina el indice sku
            $table->dropIndex('sku');

            //Elimina la columna barcode
            $table->dropColumn('barcode');
        });
    }
};
