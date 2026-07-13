<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('policy_id')
                ->constrained('payroll_policies')->restrictOnDelete();
            $table->foreignId('department_id')
                ->nullable()->constrained('departments')->nullOnDelete();
            $table->string('designation', 150)->nullable();
            $table->string('employment_type', 30)->default('monthly');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('payment_account_id')
                ->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->string('payment_method', 30)->nullable()->default('cash');
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_routing_number', 30)->nullable();
            $table->unsignedTinyInteger('monthly_working_days')->default(26);
            $table->unsignedTinyInteger('weekly_off_days')->default(1);
            $table->decimal('overtime_rate', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'user_id', 'is_active']);
            $table->index(['shop_id', 'department_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('employee_salary_structures'); }
};