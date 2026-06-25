<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained('credit_notes')->cascadeOnDelete();
            $table->foreignId('original_sale_item_id')->constrained('sale_items')->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            // Price snapshot from original sale
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku');
            $table->string('serial_number')->nullable();

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);   // original selling price
            $table->decimal('unit_cost', 12, 2);    // original cost (for COGS reversal)
            $table->decimal('line_total', 12, 2);   // refund amount for this line

            $table->string('condition');
            $table->boolean('restock')->default(true);
            $table->foreignId('restock_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->text('condition_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('credit_note_items'); }
};