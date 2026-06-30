<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Tracks cumulative returned quantity per line item across
            // multiple separate return events (e.g. customer returns 1 of 3
            // accessories today, another tomorrow). This is what makes
            // multi-item partial returns possible without blocking the whole sale.
            $table->unsignedInteger('returned_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });
    }
};