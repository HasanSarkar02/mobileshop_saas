<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('to_number');
            $table->string('template');     // 'sale_receipt' | 'due_reminder' | 'low_stock' | 'custom'
            $table->text('message');
            $table->string('status');       // 'sent' | 'failed' | 'queued'
            $table->string('provider_response')->nullable();
            $table->string('message_id')->nullable(); // provider's message ID
            $table->decimal('cost', 8, 4)->default(0);
            $table->nullableMorphs('reference');     // Sale, Customer, etc.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('sms_logs'); }
};