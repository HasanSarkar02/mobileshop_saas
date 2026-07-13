<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('run_number', 30);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('period_from');
            $table->date('period_to');

            $table->foreignId('branch_id')
                ->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')
                ->nullable()->constrained('departments')->nullOnDelete();
            $table->string('employment_type', 30)->nullable();

            // Aggregates
            $table->unsignedSmallInteger('total_employees')->default(0);
            $table->decimal('total_gross_earnings', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_net_payable', 14, 2)->default(0);
            $table->decimal('total_paid', 14, 2)->default(0);

            $table->string('status', 30)->default('draft')->index();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Approval chain
            $table->foreignId('generated_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('reversed_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();

            // GL links
            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();

            $table->unique(['shop_id', 'run_number']);
            $table->index(['shop_id', 'year', 'month', 'status'],'ym_status_index');
            $table->index(['shop_id', 'branch_id', 'year', 'month'],'branch_month_index');
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_runs'); }
};