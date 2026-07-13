<?php

namespace App\Console\Commands;

use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollComponent;
use App\Models\PayrollPolicy;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateEmployeeProfiles extends Command
{
    protected $signature   = 'payroll:migrate-employee-profiles';
    protected $description = 'Migrate old employee_profiles to new salary structure system.';

    public function handle(): void
    {
        $profiles = DB::table('employee_profiles')
            ->join('users', 'users.id', '=', 'employee_profiles.user_id')
            ->select('employee_profiles.*', 'users.shop_id')
            ->get();

        if ($profiles->isEmpty()) {
            $this->info('No employee profiles found to migrate.');
            return;
        }

        $basicComponent = PayrollComponent::withoutGlobalScopes()
            ->whereNull('shop_id')
            ->where('code', 'BASIC')
            ->first();

        $hraComponent = PayrollComponent::withoutGlobalScopes()
            ->whereNull('shop_id')
            ->where('code', 'HRA')
            ->first();

        foreach ($profiles as $profile) {
            $policy = PayrollPolicy::withoutGlobalScopes()
                ->where('shop_id', $profile->shop_id)
                ->where('is_default', true)
                ->first();

            if (! $policy) {
                // Create default policy for this shop
                $policy = PayrollPolicy::create([
                    'shop_id'          => $profile->shop_id,
                    'name'             => 'Standard Monthly Policy',
                    'code'             => 'STANDARD',
                    'employment_type'  => 'monthly',
                    'is_default'       => true,
                    'is_active'        => true,
                ]);
            }

            // Skip if already migrated
            if (EmployeeSalaryStructure::withoutGlobalScopes()
                ->where('user_id', $profile->user_id)
                ->where('is_active', true)
                ->exists()) {
                $this->line("⏭ Skipped (already has structure): User #{$profile->user_id}");
                continue;
            }

            $structure = EmployeeSalaryStructure::create([
                'shop_id'              => $profile->shop_id,
                'user_id'              => $profile->user_id,
                'policy_id'            => $policy->id,
                'designation'          => $profile->designation ?? null,
                'employment_type'      => 'monthly',
                'effective_from'       => $profile->joining_date ?? now()->toDateString(),
                'monthly_working_days' => 26,
                'weekly_off_days'      => 1,
                'is_active'            => true,
                'created_by'           => 1,
            ]);

            // Migrate base_salary as BASIC component
            if ($profile->base_salary > 0 && $basicComponent) {
                EmployeeSalaryComponent::create([
                    'salary_structure_id' => $structure->id,
                    'component_id'        => $basicComponent->id,
                    'calculation_type'    => 'fixed',
                    'value'               => $profile->base_salary,
                    'is_active'           => true,
                ]);
            }

            // Migrate house_allowance as HRA component
            if (($profile->house_allowance ?? 0) > 0 && $hraComponent) {
                EmployeeSalaryComponent::create([
                    'salary_structure_id' => $structure->id,
                    'component_id'        => $hraComponent->id,
                    'calculation_type'    => 'fixed',
                    'value'               => $profile->house_allowance,
                    'is_active'           => true,
                ]);
            }

            $this->line("✓ Migrated: User #{$profile->user_id}");
        }

        $this->info("Migration complete.");
    }
}