<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('reversal_of_id')->nullable()
                ->constrained('stock_adjustments')->nullOnDelete();
            $table->foreignId('reversed_by_id')->nullable()
                ->constrained('stock_adjustments')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of_id');
            $table->dropConstrainedForeignId('reversed_by_id');
            $table->dropColumn(['reversed_at', 'reversal_reason']);
        });
    }
};
