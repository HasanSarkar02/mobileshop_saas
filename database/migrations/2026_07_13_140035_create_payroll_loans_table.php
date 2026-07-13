<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('loan_number', 30);
            $table->enum('loan_type', ['advance', 'loan'])->default('advance');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('outstanding_balance', 14, 2);
            $table->decimal('monthly_deduction', 12, 2);
            $table->string('purpose', 255)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'fully_recovered', 'waived', 'cancelled'])
                ->default('active')->index();

            // Disbursement
            $table->foreignId('disbursement_account_id')
                ->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->date('disbursement_date');
            $table->foreignId('disbursement_journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();

            // Waiver
            $table->decimal('waived_amount', 14, 2)->default(0);
            $table->foreignId('waived_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->foreignId('waiver_journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();

            // Approval
            $table->foreignId('approved_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'loan_number']);
            $table->index(['shop_id', 'user_id', 'status'],'shp_uss_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_loans'); }
};