<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Junction table: settlement ←→ receivable
        // One settlement can settle many receivables.
        // One receivable can be partially settled across many settlements.
        Schema::create('finance_partner_settlement_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')
                ->constrained('finance_partner_settlements')
                ->cascadeOnDelete();
            $table->foreignId('receivable_id')
                ->constrained('finance_partner_receivables')
                ->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index('settlement_id');
            $table->index('receivable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_partner_settlement_allocations');
    }
};