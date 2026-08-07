<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('used_phone_acquisitions', function (Blueprint $table) {
            $table->string('seller_photo_path')->nullable()->after('seller_address');
            $table->string('seller_nid_front_path')->nullable()->after('seller_photo_path');
            $table->string('seller_nid_back_path')->nullable()->after('seller_nid_front_path');
        });
    }

    public function down(): void
    {
        Schema::table('used_phone_acquisitions', function (Blueprint $table) {
            $table->dropColumn(['seller_photo_path', 'seller_nid_front_path', 'seller_nid_back_path']);
        });
    }
};