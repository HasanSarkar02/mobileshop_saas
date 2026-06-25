<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('description');
            $table->string('receipt_path')->nullable();
            $table->string('status')->default('approved'); // most expenses auto-approved
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'expense_date']);
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'expense_category_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('expenses'); }
};