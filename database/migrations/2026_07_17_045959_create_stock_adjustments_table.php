<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->string('adjustment_type');
            // damaged | written_off | reserved | unreserved | correction
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'adjustment_type', 'created_at']);
            $table->index(['product_variant_id', 'branch_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock_adjustments'); }
};