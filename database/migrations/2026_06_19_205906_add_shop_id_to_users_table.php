<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')
                ->constrained('shops')->cascadeOnDelete();
            $table->string('user_type')->default('employee')->after('shop_id');
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('user_type');
            $table->timestamp('last_login_at')->nullable();

            $table->index(['shop_id', 'user_type']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn(['shop_id', 'user_type', 'phone', 'is_active', 'last_login_at']);
        });
    }
};