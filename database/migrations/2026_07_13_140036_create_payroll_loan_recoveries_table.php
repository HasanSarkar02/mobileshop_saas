<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_loan_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')
                ->constrained('payroll_loans')->restrictOnDelete();
            $table->foreignId('slip_id')
                ->constrained('payroll_slips')->restrictOnDelete();
            $table->decimal('amount_recovered', 12, 2);
            $table->decimal('balance_after', 14, 2);
            $table->date('recovery_date');
            $table->timestamps();

            $table->index('loan_id');
            $table->index('slip_id');
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_loan_recoveries'); }
};