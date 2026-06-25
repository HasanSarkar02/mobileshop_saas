<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('used_phone_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('acquisition_number');

            // Seller info (individual, not a registered supplier)
            $table->string('seller_name');
            $table->string('seller_phone')->nullable();
            $table->string('seller_nid')->nullable();
            $table->text('seller_address')->nullable();

            // Phone info
            $table->string('imei_1');
            $table->string('imei_2')->nullable();
            $table->string('model_description');    // free text e.g. "Samsung Galaxy A52 128GB Blue"
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            $table->string('condition');
            $table->text('condition_notes')->nullable();
            $table->string('accessories')->nullable(); // comma-separated

            // Financial
            $table->decimal('purchase_price', 12, 2);    // what we paid
            $table->decimal('expected_sell_price', 12, 2)->default(0);
            $table->foreignId('payment_account_id')->constrained('payment_accounts')->restrictOnDelete();

            // Linked to a sale if this was a trade-in during POS
            $table->foreignId('trade_in_sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'acquisition_number']);
            $table->index(['shop_id', 'branch_id']);
            $table->index('imei_1');
        });
    }

    public function down(): void { Schema::dropIfExists('used_phone_acquisitions'); }
};