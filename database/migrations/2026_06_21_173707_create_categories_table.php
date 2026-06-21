<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_categories_table.php
public function up(): void
{
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->nullable()->constrained('shops')->restrictOnDelete();
        $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
        $table->string('name');
        $table->string('default_tracking_type')->default('non_serialized');
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['shop_id', 'parent_id', 'name']);
    });
}

public function down(): void { Schema::dropIfExists('categories'); }
};
