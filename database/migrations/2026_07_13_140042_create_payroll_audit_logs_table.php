<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('user_id')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_type', 50)->index();
            $table->unsignedBigInteger('reference_id')->index();
            $table->string('action', 50)->index();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['shop_id', 'reference_type', 'reference_id'],'shp_ref_idx');
            $table->index(['shop_id', 'user_id', 'created_at'],'shp_ussr_idx');
            $table->index(['shop_id', 'action', 'created_at'],'shp_act_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('payroll_audit_logs'); }
};