<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row settings table. Row id=1 is the only row that should
     * ever exist — see App\Models\PlatformSetting::current().
     */
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('SmartShop ERP');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('default_currency', 3)->default('BDT');
            $table->string('default_timezone')->default('Asia/Dhaka');
            $table->unsignedSmallInteger('default_trial_days')->default(14);
            $table->string('terms_url')->nullable();
            $table->string('privacy_url')->nullable();
            $table->string('footer_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};