<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->string('name');
            $table->string('gl_account_code', 20)->nullable(); // links to accounts.code
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void { Schema::dropIfExists('expense_categories'); }
};