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
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1–12
            $table->string('status')->default('draft');
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'year', 'month']);
            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_runs'); }
};