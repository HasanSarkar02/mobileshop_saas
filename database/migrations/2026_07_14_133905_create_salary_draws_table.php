<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->date('draw_date');
            $table->unsignedSmallInteger('for_year');
            $table->unsignedTinyInteger('for_month'); // which payroll month this draw belongs to
            $table->string('draw_type')->default('salary'); // 'salary' | 'bonus' | 'advance'
            $table->text('notes')->nullable();
            $table->foreignId('payroll_item_id')->nullable()->constrained('payroll_items')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'user_id', 'for_year', 'for_month']);
        });
    }

    public function down(): void { Schema::dropIfExists('salary_draws'); }
};