<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_due_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('followup_type', 20);
            $table->date('followup_date');

            $table->date('promised_payment_date')->nullable();
            $table->decimal('promised_amount', 12, 2)->nullable();
            $table->date('next_followup_date')->nullable();

            $table->string('status', 30)->default('pending');
            $table->text('customer_response')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['shop_id', 'customer_id', 'created_at'], 'followup_customer_timeline');
            $table->index(['shop_id', 'next_followup_date', 'status'], 'followup_next_date_status');
            $table->index(['shop_id', 'promised_payment_date', 'status'], 'followup_promise_date_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_due_followups');
    }
};