<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('category', 40);
            $table->string('channel', 20);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // No row for a (category, channel) pair = "use the event type's
            // default" — see NotificationDispatcher::effectiveChannels(). This
            // table only ever stores explicit overrides, never a full pre-seeded
            // matrix per user.
            $table->unique(['user_id', 'category', 'channel'], 'notif_pref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};