<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::rename('branch_stock', 'branch_stocks');
}

public function down(): void
{
    Schema::rename('branch_stocks', 'branch_stock');
}
};
