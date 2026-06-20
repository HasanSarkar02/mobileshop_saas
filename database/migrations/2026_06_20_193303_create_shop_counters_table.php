<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('counter_key');
            $table->unsignedBigInteger('current_value')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'counter_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_counters');
    }
};