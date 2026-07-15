<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->restrictOnDelete();
            $table->string('event_type', 60);
            $table->string('channel', 20);
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'event_type', 'channel'], 'notif_template_unique');
            $table->index(['event_type', 'channel'], 'notif_template_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};