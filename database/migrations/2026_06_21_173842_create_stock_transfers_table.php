<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_stock_transfers_table.php
public function up(): void
{
    Schema::create('stock_transfers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->foreignId('from_branch_id')->constrained('branches')->restrictOnDelete();
        $table->foreignId('to_branch_id')->constrained('branches')->restrictOnDelete();
        $table->string('status')->default('pending');
        $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('initiated_at')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->timestamps();

        $table->index(['shop_id', 'status']);
    });
}

public function down(): void { Schema::dropIfExists('stock_transfers'); }
};
