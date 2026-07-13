<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->enum('component_type', ['earning', 'deduction'])->index();
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])
                ->default('fixed');
            $table->decimal('default_value', 12, 4)->nullable();
            $table->string('percentage_of', 50)->nullable();
            $table->text('formula')->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('affects_gross')->default(true);
            $table->smallInteger('sequence')->default(100);
            $table->string('gl_account_code', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
            $table->index(['shop_id', 'component_type', 'is_active'],'shop_component_index');
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_components'); }
};