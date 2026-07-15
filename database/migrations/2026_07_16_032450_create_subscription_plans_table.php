<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // Basic, Pro, Enterprise
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('yearly_price',  10, 2)->nullable();
            $table->integer('max_branches')->default(1);
            $table->integer('max_employees')->default(5);
            $table->integer('max_products')->default(500);
            $table->json('features')->nullable();          // ['payroll','treasury',...]
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('subscription_plans'); }
};