<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve old data by renaming — never delete historical payroll
        if (Schema::hasTable('payroll_runs') === false &&
            Schema::hasTable('legacy_payroll_runs') === false) {
            return; // tables don't exist yet (fresh install)
        }

        // Only rename if old-style tables exist (identified by missing run_number)
        if (Schema::hasTable('payroll_runs') &&
            ! Schema::hasColumn('payroll_runs', 'run_number')) {
            Schema::rename('payroll_runs', 'legacy_payroll_runs');
        }

        if (Schema::hasTable('payroll_items') &&
            ! Schema::hasTable('legacy_payroll_items')) {
            Schema::rename('payroll_items', 'legacy_payroll_items');
        }

        if (Schema::hasTable('salary_draws') &&
            ! Schema::hasTable('legacy_salary_draws')) {
            Schema::rename('salary_draws', 'legacy_salary_draws');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('legacy_payroll_runs') &&
            ! Schema::hasTable('payroll_runs')) {
            Schema::rename('legacy_payroll_runs', 'payroll_runs');
        }
    }
};