<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._add_onboarding_completed_at_to_shops_table.php
public function up(): void
{
    Schema::table('shops', function (Blueprint $table) {
        $table->timestamp('onboarding_completed_at')->nullable();
    });
}

public function down(): void
{
    Schema::table('shops', function (Blueprint $table) {
        $table->dropColumn('onboarding_completed_at');
    });
}
};
