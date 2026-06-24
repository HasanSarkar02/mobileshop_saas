<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            // Price snapshot — immutable after confirm; historical P&L always accurate
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku');
            $table->string('serial_number')->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('original_price', 12, 2);
            $table->decimal('cost_price', 12, 2);

            $table->string('discount_type')->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->decimal('line_subtotal', 12, 2);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->decimal('profit_amount', 12, 2)->default(0);

            $table->timestamps();

            $table->index('sale_id');
            $table->index('product_unit_id');
        });
    }

    public function down(): void { Schema::dropIfExists('sale_items'); }
};