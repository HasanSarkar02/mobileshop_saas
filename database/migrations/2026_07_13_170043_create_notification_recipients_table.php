<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Denormalized — a user belongs to exactly one shop today, but this
            // avoids a join on every "my notifications" query at scale.
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();

            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('pinned_at')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('action_taken_at')->nullable();

            $table->timestamps();

            $table->unique(['notification_id', 'user_id'], 'notif_recipient_unique');

            // Covers the "active bell list" query: not archived/dismissed, not
            // currently snoozed, newest first.
            $table->index(
                ['user_id', 'shop_id', 'archived_at', 'dismissed_at', 'snoozed_until', 'created_at'],
                'notif_recipient_active_list'
            );
            $table->index(['user_id', 'shop_id', 'read_at'], 'notif_recipient_unread');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};