<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_products_table.php
public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
        $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
        $table->string('name');
        $table->string('tracking_type')->default('non_serialized');
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();

        $table->index(['shop_id', 'is_active']);
    });
}

public function down(): void { Schema::dropIfExists('products'); }
};
