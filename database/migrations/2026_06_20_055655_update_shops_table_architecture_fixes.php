<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('owner_name');
            $table->boolean('vat_enabled')->default(false)->after('currency');
            $table->string('vat_registration_number')->nullable()->after('vat_enabled');
            $table->decimal('default_vat_rate', 5, 2)->default(0)->after('vat_registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('slug');
            $table->dropColumn(['vat_enabled', 'vat_registration_number', 'default_vat_rate']);
        });
    }
};