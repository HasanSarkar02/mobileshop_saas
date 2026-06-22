<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->text('address')->nullable();
            $table->string('relation')->default('other');
            $table->string('nid_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('nid_front_path')->nullable();
            $table->string('nid_back_path')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'customer_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('customer_guarantors'); }
};