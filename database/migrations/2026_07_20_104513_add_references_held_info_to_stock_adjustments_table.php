<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->nullableMorphs('reference'); // reference_type / reference_id — links a reservation to its originating record (e.g. a held Sale)
            $table->string('held_for_name', 150)->nullable()->after('reference_id');
            $table->string('held_for_phone', 30)->nullable()->after('held_for_name');
            $table->timestamp('hold_expires_at')->nullable()->after('held_for_phone');

            $table->index(['shop_id', 'adjustment_type', 'hold_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'adjustment_type', 'hold_expires_at']);
            $table->dropColumn(['reference_type', 'reference_id', 'held_for_name', 'held_for_phone', 'hold_expires_at']);
        });
    }
};