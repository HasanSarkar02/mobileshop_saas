<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('info');       // info | warning | critical
            $table->string('audience')->default('both');    // shop_app | admin_panel | both
            $table->boolean('is_active')->default(true);
            $table->boolean('dismissible')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
    }
};