<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('sale_number');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('confirmed');

            // Financial snapshot
            $table->decimal('subtotal', 14, 2)->default(0);           // Σ(qty × unit_price)
            $table->string('order_discount_type')->default('none');
            $table->decimal('order_discount_value', 10, 2)->default(0);
            $table->decimal('item_discount_amount', 12, 2)->default(0);
            $table->decimal('order_discount_amount', 12, 2)->default(0);
            $table->decimal('total_discount_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('gross_profit', 14, 2)->default(0);
            $table->decimal('due_collection_amount', 12, 2)->default(0);

            // Meta
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->foreignId('void_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'sale_number']);
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'customer_id']);
            $table->index(['shop_id', 'confirmed_at']);
            $table->index(['branch_id', 'confirmed_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('sales'); }
};