<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();

            // Identity
            $table->string('transaction_number', 30);
            $table->string('transaction_type', 30)->index();
            $table->string('transaction_category', 30)->index();

            // Lifecycle
            $table->string('status', 25)->default('draft');

            // Money movement
            $table->foreignId('from_payment_account_id')
                ->nullable()->constrained('payment_accounts')->restrictOnDelete();
            $table->foreignId('to_payment_account_id')
                ->nullable()->constrained('payment_accounts')->restrictOnDelete();

            $table->decimal('amount', 14, 2);          // gross amount
            $table->decimal('fee_amount', 12, 2)->default(0); // bank fee / MFS fee / interest
            $table->decimal('net_amount', 14, 2);      // amount - fee_amount (what arrives)

            // Dates
            $table->date('transaction_date');
            $table->date('value_date')->nullable(); // bank clearing date (future reconciliation)

            // Narrative — required for every transaction
            $table->string('description', 500);
            $table->string('reference_number', 100)->nullable();

            // Third party (lender, partner, bank name)
            $table->string('third_party_name', 255)->nullable();
            $table->string('third_party_reference', 255)->nullable();

            // Approval workflow
            $table->boolean('approval_required')->default(false);
            $table->decimal('approval_threshold_snapshot', 12, 2)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Accounting link — set on completion, never before
            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();

            // Reversal chain — immutable audit trail
            $table->foreignId('reversal_of_id')
                ->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->foreignId('reversed_by_id')
                ->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();

            // Attachments — JSON array of storage paths
            $table->json('attachments')->nullable();

            // Internal notes
            $table->text('notes')->nullable();

            // Future-proof: currency, exchange_rate, bank_reconciled_at
            $table->json('metadata')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Constraints
            $table->unique(['shop_id', 'transaction_number']);

            // Indexes — aligned with architecture review
            $table->index(['shop_id', 'transaction_date', 'status'], 'trx_shop_date_status');
            $table->index(['shop_id', 'transaction_type', 'status'], 'trx_shop_type_status');
            $table->index(['shop_id', 'transaction_category'],       'trx_shop_category');
            $table->index(['shop_id', 'status', 'approval_required'],'trx_pending');
            $table->index(['from_payment_account_id', 'transaction_date'], 'trx_from_acc_date');
            $table->index(['to_payment_account_id',   'transaction_date'], 'trx_to_acc_date');
        });
    }

    public function down(): void { Schema::dropIfExists('treasury_transactions'); }
};