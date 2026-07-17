
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('barcode', 100)->nullable()->after('sku');
            $table->unsignedSmallInteger('min_stock_level')->nullable()->after('barcode');
            // null = use shop global threshold

            $table->index(['shop_id', 'barcode']);
        });
    }
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'min_stock_level']);
        });
    }
};
