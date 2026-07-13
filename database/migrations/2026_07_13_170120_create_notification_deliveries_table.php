<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_recipient_id')
                ->constrained('notification_recipients')->cascadeOnDelete();

            $table->string('channel', 20);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            // e.g. maps to sms_logs.id, a future FCM message id, an SMTP message-id.
            // Deliberately untyped so any future channel can populate it without a
            // new column.
            $table->string('provider_reference', 191)->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->unique(['notification_recipient_id', 'channel'], 'notif_delivery_unique');
            $table->index(['channel', 'status'], 'notif_delivery_channel_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};