<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_purchases_table.php
public function up(): void
{
    Schema::create('purchases', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
        $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
        $table->string('reference_number');
        $table->date('purchase_date');
        $table->decimal('total_amount', 14, 2)->default(0);
        $table->string('payment_status')->default('unpaid');
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();

        $table->unique(['shop_id', 'reference_number']);
        $table->index(['shop_id', 'purchase_date']);
    });
}

public function down(): void { Schema::dropIfExists('purchases'); }
};
