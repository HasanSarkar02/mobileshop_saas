<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('payroll_run_id')
                ->constrained('payroll_runs')->restrictOnDelete();
            $table->foreignId('slip_id')
                ->constrained('payroll_slips')->restrictOnDelete();
            $table->string('payment_number', 30);
            $table->foreignId('payment_account_id')
                ->constrained('payment_accounts')->restrictOnDelete();
            $table->string('payment_method', 30)->default('cash');
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['paid', 'reversed'])->default('paid');

            // GL links
            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();

            // Reversal tracking
            $table->foreignId('reversed_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();

            $table->foreignId('created_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'payment_number']);
            $table->index(['slip_id', 'status']);
            $table->index(['payroll_run_id']);
            $table->index(['shop_id', 'payment_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_payments'); }
};