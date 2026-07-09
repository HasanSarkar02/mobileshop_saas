<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Separate threshold from expense_approval_threshold — different contexts
            $table->decimal('treasury_approval_threshold', 12, 2)
                ->default(10000)
                ->after('expense_approval_threshold');
            $table->decimal('petty_cash_limit', 10, 2)
                ->default(5000)
                ->after('treasury_approval_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['treasury_approval_threshold', 'petty_cash_limit']);
        });
    }
};