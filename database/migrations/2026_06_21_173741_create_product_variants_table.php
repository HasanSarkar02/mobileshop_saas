<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_product_variants_table.php
public function up(): void
{
    Schema::create('product_variants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
        $table->string('sku');
        $table->string('attributes_label')->nullable();
        $table->json('attributes')->nullable();
        $table->decimal('selling_price', 12, 2);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();

        $table->unique(['shop_id', 'sku']);
        $table->index('product_id');
    });
}

public function down(): void { Schema::dropIfExists('product_variants'); }
};
