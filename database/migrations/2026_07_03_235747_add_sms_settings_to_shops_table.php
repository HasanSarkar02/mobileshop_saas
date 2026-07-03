<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('sms_enabled')->default(false)->after('show_document_confidential');
            $table->string('sms_provider')->default('bulk_sms_bd')->after('sms_enabled');
            $table->string('sms_api_key')->nullable()->after('sms_provider');
            $table->string('sms_sender_id')->nullable()->after('sms_api_key');
            // Feature toggles
            $table->boolean('sms_on_sale')->default(false)->after('sms_sender_id');
            $table->boolean('sms_on_due_reminder')->default(false)->after('sms_on_sale');
            $table->boolean('sms_on_service_ready')->default(false)->after('sms_on_due_reminder');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'sms_enabled', 'sms_provider', 'sms_api_key',
                'sms_sender_id', 'sms_on_sale', 'sms_on_due_reminder', 'sms_on_service_ready',
            ]);
        });
    }
};