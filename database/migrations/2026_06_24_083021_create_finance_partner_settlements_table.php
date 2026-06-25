<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_partner_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('finance_partner_id')->constrained('finance_partners')->restrictOnDelete();
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->decimal('gross_amount', 12, 2);      // what they should have sent
            $table->decimal('fee_deducted', 12, 2)->default(0); // fee deducted by partner
            $table->decimal('net_amount', 12, 2);        // what actually arrived
            $table->decimal('allocated_amount', 12, 2)->default(0); // sum of allocations
            $table->date('settlement_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'finance_partner_id']);
            $table->index(['shop_id', 'settlement_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('finance_partner_settlements'); }
};