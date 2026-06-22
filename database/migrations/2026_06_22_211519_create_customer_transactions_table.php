<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2);
            $table->string('direction'); // 'debit' (owes more) | 'credit' (owes less)
            $table->decimal('running_balance', 12, 2); // balance AFTER this transaction
            $table->nullableMorphs('reference'); // links to Sale, Return, etc.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'customer_id']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('customer_transactions'); }
};