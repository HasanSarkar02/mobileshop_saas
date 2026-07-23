<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('suspension_reason')->nullable()->after('is_active');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
        });
    }
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['suspension_reason', 'suspended_at']);
        });
    }
};