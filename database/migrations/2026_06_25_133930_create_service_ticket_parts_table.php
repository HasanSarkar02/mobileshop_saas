<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('part_description'); // free-text for external parts
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->boolean('from_inventory')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('service_ticket_parts'); }
};