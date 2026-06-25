<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('ticket_number');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Customer info snapshot (for walk-in without account)
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();

            // Device info
            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('device_imei')->nullable();
            $table->string('device_color')->nullable();
            $table->string('device_condition')->nullable(); // physical condition on receipt

            // Service info
            $table->text('problem_description');
            $table->text('diagnosis_notes')->nullable();
            $table->text('internal_notes')->nullable();

            // Pricing
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('labor_charge', 12, 2)->default(0);
            $table->decimal('total_charge', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_due', 12, 2)->default(0);

            // Status
            $table->string('status')->default('received');
            $table->boolean('is_warranty_service')->default(false);
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            // Technician
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();

            // Timestamps
            $table->timestamp('received_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'ticket_number']);
            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('service_tickets'); }
};