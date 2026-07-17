<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_stocks', function (Blueprint $table) {
            $table->decimal('reserved_quantity', 10, 2)->default(0)->after('quantity');
            $table->decimal('damaged_quantity',  10, 2)->default(0)->after('reserved_quantity');
        });
    }
    public function down(): void
    {
        Schema::table('branch_stocks', function (Blueprint $table) {
            $table->dropColumn(['reserved_quantity', 'damaged_quantity']);
        });
    }
};