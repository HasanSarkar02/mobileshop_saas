<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')
                ->constrained('employee_salary_structures')->cascadeOnDelete();
            $table->foreignId('component_id')
                ->constrained('payroll_components')->restrictOnDelete();
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])
                ->default('fixed');
            $table->decimal('value', 14, 4)->default(0);
            $table->string('percentage_of', 50)->nullable();
            $table->text('formula')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['salary_structure_id', 'component_id'],'salary_component_unq');
        });
    }

    public function down(): void { Schema::dropIfExists('employee_salary_components'); }
};