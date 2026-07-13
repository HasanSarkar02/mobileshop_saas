<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('parent_department_id')
                ->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 30)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('head_user_id')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'code']);
            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void { Schema::dropIfExists('departments'); }
};