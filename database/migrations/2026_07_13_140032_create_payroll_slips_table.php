<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('payroll_run_id')
                ->constrained('payroll_runs')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Snapshot fields (immutable after generation)
            $table->string('employee_name', 255);
            $table->string('designation', 150)->nullable();
            $table->string('department_name', 150)->nullable();
            $table->string('employment_type', 30);

            // Attendance
            $table->unsignedTinyInteger('working_days')->default(26);
            $table->decimal('days_worked', 5, 2)->default(26);
            $table->decimal('leaves_paid', 5, 2)->default(0);
            $table->decimal('leaves_unpaid', 5, 2)->default(0);
            $table->decimal('absent_days', 5, 2)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);

            // Financials
            $table->decimal('gross_earnings', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->decimal('balance_payable', 14, 2)->default(0);

            $table->string('status', 30)->default('draft')->index();

            // Payment preference
            $table->foreignId('payment_account_id')
                ->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->string('payment_method', 30)->nullable()->default('cash');

            // GL links
            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'user_id']);
            $table->index(['payroll_run_id', 'status']);
            $table->index(['shop_id', 'user_id', 'status'],'shp_us_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_slips'); }
};