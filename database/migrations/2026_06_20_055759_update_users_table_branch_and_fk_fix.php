<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Shop is the core tenant boundary — never silently cascade-delete
            // a user just because someone deletes a shop row.
            $table->foreign('shop_id')->references('id')->on('shops')->restrictOnDelete();

            // Owner stays null (= access to all branches). Employees can be
            // restricted to one branch. Nullable so single-branch shops are unaffected.
            $table->foreignId('branch_id')->nullable()->after('shop_id')
                ->constrained('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->dropForeign(['shop_id']);
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
        });
    }
};