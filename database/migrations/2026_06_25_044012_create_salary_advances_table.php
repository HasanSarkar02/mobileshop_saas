<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_remaining', 12, 2);
            $table->decimal('monthly_deduction', 12, 2)->default(0);
            $table->date('advance_date');
            $table->string('purpose')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'user_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('salary_advances'); }
};