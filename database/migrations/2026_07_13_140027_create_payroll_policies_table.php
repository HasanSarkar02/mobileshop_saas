<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->string('employment_type', 30)->default('monthly');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_policies'); }
};