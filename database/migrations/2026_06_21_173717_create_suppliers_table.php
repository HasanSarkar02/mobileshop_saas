<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_suppliers_table.php
public function up(): void
{
    Schema::create('suppliers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->string('name');
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->text('address')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();

        $table->index(['shop_id', 'is_active']);
    });
}

public function down(): void { Schema::dropIfExists('suppliers'); }
};
