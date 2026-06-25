<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('voided_by')->nullable()
                ->after('approved_at')
                ->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
            $table->timestamp('voided_at')->nullable()->after('void_reason');
            $table->foreignId('rejected_by')->nullable()
                ->after('voided_at')
                ->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('rejected_by');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'voided_by', 'void_reason', 'voided_at',
                'rejected_by', 'rejection_reason', 'rejected_at',
            ]);
        });
    }
};