<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('designation')->nullable();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('house_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->date('joining_date')->nullable();
            $table->string('nid_number')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->foreignId('salary_payment_account_id')->nullable()
                ->constrained('payment_accounts')->nullOnDelete();
            $table->timestamps();

            $table->index('shop_id');
        });
    }

    public function down(): void { Schema::dropIfExists('employee_profiles'); }
};