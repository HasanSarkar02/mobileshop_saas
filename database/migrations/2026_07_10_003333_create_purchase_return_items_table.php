<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')
                ->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('purchase_line_item_id')
                ->constrained('purchase_line_items')->restrictOnDelete();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('product_unit_id')
                ->nullable()->constrained('product_units')->nullOnDelete();

            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('condition')->default('good'); // good | damaged | defective
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('purchase_return_items'); }
};