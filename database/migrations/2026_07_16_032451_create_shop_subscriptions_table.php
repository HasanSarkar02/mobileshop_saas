<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('billing_cycle')->default('monthly'); // monthly | yearly
            $table->decimal('price_at_signup', 10, 2);
            $table->string('status')->default('trial');
            // trial | active | past_due | suspended | cancelled
            $table->date('trial_ends_at')->nullable();
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->date('next_billing_date')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('payment_reference')->nullable(); // SSLCommerz TxnID
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['next_billing_date', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('shop_subscriptions'); }
};