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
    Schema::create('product_units', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
        $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
        $table->string('serial_number');
        $table->string('secondary_serial_number')->nullable();
        $table->decimal('cost_price', 12, 2);
        $table->foreignId('purchase_line_item_id')->nullable()->constrained('purchase_line_items')->nullOnDelete();
        $table->string('status')->default('in_stock');
        $table->nullableMorphs('disposition');
        $table->unsignedInteger('manufacturer_warranty_months')->default(0);
        $table->unsignedInteger('shop_warranty_days')->default(0);
        $table->timestamp('sold_at')->nullable();
        $table->boolean('is_archived')->default(false);
        $table->timestamps();

        $table->index(['shop_id', 'status']);
        $table->index(['branch_id', 'status']);
        $table->index('serial_number');
        $table->index('secondary_serial_number');
    });
}

public function down(): void { Schema::dropIfExists('product_units'); }
};
