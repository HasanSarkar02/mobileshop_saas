<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('subscription_id')->constrained('shop_subscriptions')->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending | paid | failed | waived
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('subscription_invoices'); }
};