<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->string('event_type', 60);
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);

            // [{"field":"amount","operator":">","value":5000}, ...] — flat AND-list,
            // deliberately not a nested boolean tree. See NotificationRuleEvaluator.
            $table->json('conditions')->nullable();

            $table->json('channel_override')->nullable(); // ['email','sms']
            $table->string('priority_override', 20)->nullable();

            $table->string('recipient_override_type', 20)->nullable(); // 'permission' | 'users'
            $table->string('recipient_override_permission', 100)->nullable();
            $table->json('recipient_override_user_ids')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'event_type', 'is_active'], 'notif_rule_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};