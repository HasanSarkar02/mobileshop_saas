<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('credit_note_number');
            $table->foreignId('original_sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('status')->default('completed');
            $table->string('refund_method');

            // Financial snapshot
            $table->decimal('items_total', 12, 2)->default(0);    // sum of line totals
            $table->decimal('refund_amount', 12, 2)->default(0);  // actual refund (after adjustments)
            $table->decimal('restock_value', 12, 2)->default(0);  // inventory value being restocked

            // Refund payment details (for original_payment method)
            $table->foreignId('refund_payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->string('refund_reference')->nullable();

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'credit_note_number']);
            $table->index(['shop_id', 'original_sale_id']);
            $table->index(['shop_id', 'customer_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('credit_notes'); }
};