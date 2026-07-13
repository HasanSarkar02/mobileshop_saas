<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_policy_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')
                ->constrained('payroll_policies')->cascadeOnDelete();
            $table->foreignId('component_id')
                ->constrained('payroll_components')->restrictOnDelete();
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])
                ->default('fixed');
            $table->decimal('default_value', 14, 4)->default(0);
            $table->string('percentage_of', 50)->nullable();
            $table->text('formula')->nullable();
            $table->boolean('is_required')->default(false);
            $table->smallInteger('sequence')->default(100);
            $table->timestamps();

            $table->unique(['policy_id', 'component_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_policy_components'); }
};