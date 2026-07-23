<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();

            $table->string('external_phone', 30)->nullable()->after('user_id');
            $table->string('external_email', 191)->nullable()->after('external_phone');
            $table->string('external_name', 191)->nullable()->after('external_email');
        });
    }

    public function down(): void
    {
        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->dropColumn(['external_phone', 'external_email', 'external_name']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};