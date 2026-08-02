<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->foreignId('stock_adjustment_id')->nullable()
                ->constrained('stock_adjustments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_adjustment_id');
        });
    }
};