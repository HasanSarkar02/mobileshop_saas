<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // ..._create_impersonation_logs_table.php
public function up(): void
{
    Schema::create('impersonation_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('super_admin_id')->constrained('users')->restrictOnDelete();
        $table->foreignId('target_user_id')->constrained('users')->restrictOnDelete();
        $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
        $table->string('reason')->nullable();
        $table->timestamp('started_at');
        $table->timestamp('ended_at')->nullable();
        $table->timestamps();

        $table->index('super_admin_id');
        $table->index('target_user_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('impersonation_logs');
}
};
