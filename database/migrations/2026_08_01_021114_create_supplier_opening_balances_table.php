<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('reference_number', 30);

            $table->decimal('amount', 14, 2);
            $table->date('balance_date');
            $table->text('notes')->nullable();

            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // DB-level guarantee — never more than one opening balance per
            // supplier per shop, regardless of what the app layer does.
            $table->unique(['shop_id', 'supplier_id'], 'sob_shop_supplier_unq');
            $table->unique(['shop_id', 'reference_number'], 'sob_shop_ref_unq');
            $table->index(['shop_id', 'balance_date'], 'sob_date_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('supplier_opening_balances'); }
};