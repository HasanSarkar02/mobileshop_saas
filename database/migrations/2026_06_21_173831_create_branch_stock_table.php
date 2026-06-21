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
    Schema::create('branch_stock', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
        $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
        $table->unsignedInteger('quantity')->default(0);
        $table->decimal('average_cost', 12, 4)->default(0);
        $table->timestamps();

        $table->unique(['branch_id', 'product_variant_id']);
    });
}

public function down(): void { Schema::dropIfExists('branch_stock'); }
};
