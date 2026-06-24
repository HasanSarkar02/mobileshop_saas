<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payment_type: 'cash'|'bank'|'bkash'|'nagad'|'rocket'|'upay'|
        //               'card'|'other'|'finance_partner'|'customer_credit'
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('payment_type');
            $table->foreignId('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->foreignId('finance_partner_id')->nullable()->constrained('finance_partners')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sale_id');
        });
    }

    public function down(): void { Schema::dropIfExists('sale_payments'); }
};