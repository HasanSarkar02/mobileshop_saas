<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_stock_transfer_items_table.php
public function up(): void
{
    Schema::create('stock_transfer_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
        $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
        $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
        $table->unsignedInteger('quantity')->default(1);
        $table->timestamps();
    });
}

public function down(): void { Schema::dropIfExists('stock_transfer_items'); }
};
