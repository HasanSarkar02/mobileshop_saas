<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('service_payments'); }
};