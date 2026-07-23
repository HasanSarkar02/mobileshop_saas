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

    // Sales
    Schema::table('sales', function (Blueprint $table) {
        $table->index(['shop_id', 'sale_number']);
    });

    // Products
    Schema::table('products', function (Blueprint $table) {
        $table->index(['shop_id', 'name', 'is_active']);
    });

    // Product Units (IMEI)
    Schema::table('product_units', function (Blueprint $table) {
        $table->index(['shop_id', 'serial_number']);
    });

    // Purchases
    Schema::table('purchases', function (Blueprint $table) {
        $table->index(['shop_id', 'reference_number']);
    });

    // Suppliers
    Schema::table('suppliers', function (Blueprint $table) {
        $table->index(['shop_id', 'name']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
