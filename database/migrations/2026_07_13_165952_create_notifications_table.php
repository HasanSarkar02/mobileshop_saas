<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('event_type', 60);
            $table->string('category', 40);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('created');

            $table->string('title', 255);
            $table->text('body');
            $table->string('icon', 50)->nullable();

            $table->nullableMorphs('reference');

            $table->boolean('action_required')->default(false);
            $table->string('action_label', 100)->nullable();

            // Recurrence of "the same underlying problem" folds into one row instead
            // of spamming a new row per occurrence — see NotificationDispatcher.
            $table->string('group_key', 150)->nullable();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamp('last_occurred_at')->nullable();

            $table->unsignedTinyInteger('escalation_level')->default(0);
            $table->timestamp('escalated_at')->nullable();

            // Extensible, transport-agnostic data bag: deep-link overrides, future
            // push metadata (image_url, sound, collapse_key), digest hints. New keys
            // NEVER require a migration — only add a column if a key needs indexing.
            $table->json('payload')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'created_at'], 'notif_shop_created');
            $table->index(['shop_id', 'category', 'created_at'], 'notif_shop_category_created');
            $table->index(['shop_id', 'event_type'], 'notif_shop_event_type');
            $table->index('group_key', 'notif_group_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};