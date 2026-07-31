<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename(
            'customer_due_followups',
            'customer_due_follow_ups'
        );
    }

    public function down(): void
    {
        Schema::rename(
            'customer_due_follow_ups',
            'customer_due_followups'
        );
    }
};