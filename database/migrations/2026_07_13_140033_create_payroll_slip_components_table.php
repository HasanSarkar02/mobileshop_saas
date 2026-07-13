<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_slip_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slip_id')
                ->constrained('payroll_slips')->cascadeOnDelete();
            $table->foreignId('component_id')
                ->constrained('payroll_components')->restrictOnDelete();

            // Immutable snapshot
            $table->string('component_name', 150);
            $table->string('component_code', 50);
            $table->enum('component_type', ['earning', 'deduction']);
            $table->boolean('is_taxable')->default(false);
            $table->smallInteger('sequence')->default(100);
            $table->string('calculation_type', 30);
            $table->text('calculation_basis')->nullable(); // human-readable explanation
            $table->text('formula_used')->nullable();
            $table->decimal('computed_value', 14, 2)->default(0);

            $table->timestamps();

            $table->index(['slip_id', 'component_type', 'sequence'], 'slip_component_index');
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_slip_components'); }
};