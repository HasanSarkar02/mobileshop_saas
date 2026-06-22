<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('customer_type')->default('regular');
            $table->string('name');
            $table->string('phone')->index();
            $table->string('phone_alt')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('thana')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('occupation')->nullable();

            // Documents
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();

            // Financial — cached running totals for fast lookups
            // Always recomputable from customer_transactions
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0); // positive = owes us
            $table->decimal('total_purchase_amount', 14, 2)->default(0);
            $table->decimal('total_paid_amount', 14, 2)->default(0);

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'customer_type']);
            $table->index(['shop_id', 'phone']);
            $table->index(['shop_id', 'current_balance']);
        });
    }

    public function down(): void { Schema::dropIfExists('customers'); }
};