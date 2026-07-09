<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Denormalized running balance — always updated atomically.
            // Source of truth remains journal_entries.
            // Positive = we owe them money (they supplied on credit).
            $table->text('notes')->nullable()->after('address');
            $table->decimal('current_balance', 14, 2)->default(0)->after('notes');
            $table->decimal('credit_limit', 12, 2)->default(0)->after('current_balance');
            $table->string('bank_name')->nullable()->after('credit_limit');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_branch_name')->nullable()->after('bank_account_number');
            $table->string('bank_routing_number')->nullable()->after('bank_branch_name');
            $table->string('payment_terms')->nullable()->after('bank_routing_number');
            // e.g. 'Net 30', 'Net 15', 'COD', 'Advance'
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'current_balance', 'credit_limit',
                'bank_name', 'bank_account_number', 'bank_branch_name',
                'bank_routing_number', 'payment_terms',
            ]);
        });
    }
};