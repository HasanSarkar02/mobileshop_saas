<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('system_origin', 50)->nullable()->after('is_active');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['shop_id', 'system_origin'], 'products_shop_system_origin_unique');
        });
        DB::table('products')
            ->where(function ($q) {
                $q->where('name', 'Used Phones (Unlinked)')
                  ->orWhere('description', 'Auto-created for used phone acquisitions not linked to catalog');
            })
            ->whereNull('system_origin')
            ->update(['system_origin' => 'used_phone_bucket']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_shop_system_origin_unique');
            $table->dropColumn('system_origin');
        });
    }
};