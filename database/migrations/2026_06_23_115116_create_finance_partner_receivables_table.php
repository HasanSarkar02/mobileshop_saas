<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_partner_receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('finance_partner_id')->constrained('finance_partners')->restrictOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('settled_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('partner_reference')->nullable();
            $table->date('expected_settlement_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(
                ['shop_id', 'status'],
                'fpr_shop_status_idx'
            );

            $table->index(
                ['shop_id', 'finance_partner_id', 'status'],
                'fpr_shop_partner_status_idx'
            );
        });
    }

    public function down(): void { Schema::dropIfExists('finance_partner_receivables'); }
};