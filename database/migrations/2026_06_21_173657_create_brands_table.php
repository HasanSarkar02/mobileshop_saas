<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_brands_table.php
public function up(): void
{
    Schema::create('brands', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->nullable()->constrained('shops')->restrictOnDelete();
        $table->string('name');
        $table->string('logo')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['shop_id', 'name']);
    });
}

public function down(): void { Schema::dropIfExists('brands'); }
};
