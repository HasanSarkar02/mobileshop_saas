<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_purchase_line_items_table.php
public function up(): void
{
    Schema::create('purchase_line_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
        $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
        $table->unsignedInteger('quantity');
        $table->decimal('unit_cost', 12, 2);
        $table->decimal('line_total', 14, 2);
        $table->timestamps();
    });
}

public function down(): void { Schema::dropIfExists('purchase_line_items'); }
};
