<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Raw SQL, not ->change(), because doctrine/dbal isn't in composer.json.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE customer_due_followups MODIFY followup_date DATETIME NOT NULL');
        DB::statement('ALTER TABLE customer_due_followups MODIFY promised_payment_date DATETIME NULL');
        DB::statement('ALTER TABLE customer_due_followups MODIFY next_followup_date DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customer_due_followups MODIFY followup_date DATE NOT NULL');
        DB::statement('ALTER TABLE customer_due_followups MODIFY promised_payment_date DATE NULL');
        DB::statement('ALTER TABLE customer_due_followups MODIFY next_followup_date DATE NULL');
    }
};