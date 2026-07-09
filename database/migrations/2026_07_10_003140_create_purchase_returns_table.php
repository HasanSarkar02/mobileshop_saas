<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('return_number', 30);

            $table->decimal('total_amount', 14, 2);
            $table->date('return_date');
            $table->string('return_reason');
            $table->text('notes')->nullable();

            // How supplier settles the return
            $table->string('settlement_type')->default('credit_note');
            // credit_note | cash_refund | replacement

            // For cash_refund: which account received the money back
            $table->foreignId('refund_account_id')
                ->nullable()->constrained('payment_accounts')->nullOnDelete();

            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['shop_id', 'return_number'],'prs_unq');
            $table->index(['shop_id', 'supplier_id', 'return_date'],'prs_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('purchase_returns'); }
};