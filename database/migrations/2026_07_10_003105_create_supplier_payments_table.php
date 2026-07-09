<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->string('payment_number', 30);

            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('cash');
            // cash | bank_transfer | cheque | mobile_banking

            $table->string('reference_number')->nullable(); // cheque no, bank ref
            $table->string('bank_name')->nullable();
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['shop_id', 'payment_number'],'spp_unq');
            $table->index(['shop_id', 'supplier_id', 'payment_date'],'spp_idx');
            $table->index(['shop_id', 'payment_date'],'spp_date_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('supplier_payments'); }
};