<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('currency');
            $table->string('trade_license_number')->nullable()->after('logo_path');
            $table->string('website')->nullable()->after('trade_license_number');
            $table->text('document_footer_note')->nullable()->after('website');
            $table->boolean('show_document_confidential')->default(false)->after('document_footer_note');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path', 'trade_license_number', 'website',
                'document_footer_note', 'show_document_confidential',
            ]);
        });
    }
};