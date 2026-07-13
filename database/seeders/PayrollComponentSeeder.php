<?php

namespace Database\Seeders;

use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;

class PayrollComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            // ── EARNINGS ──────────────────────────────────────────────────────
            ['code' => 'BASIC',       'name' => 'Basic Salary',           'type' => 'earning',   'calc' => 'fixed',      'seq' => 10,  'taxable' => true],
            ['code' => 'HRA',         'name' => 'House Rent Allowance',   'type' => 'earning',   'calc' => 'percentage', 'of' => 'BASIC', 'val' => 50, 'seq' => 20, 'taxable' => false],
            ['code' => 'MEDICAL',     'name' => 'Medical Allowance',      'type' => 'earning',   'calc' => 'fixed',      'seq' => 30,  'taxable' => false],
            ['code' => 'TRANSPORT',   'name' => 'Transport Allowance',    'type' => 'earning',   'calc' => 'fixed',      'seq' => 40,  'taxable' => false],
            ['code' => 'FOOD',        'name' => 'Food Allowance',         'type' => 'earning',   'calc' => 'fixed',      'seq' => 50,  'taxable' => false],
            ['code' => 'PHONE',       'name' => 'Phone Allowance',        'type' => 'earning',   'calc' => 'fixed',      'seq' => 60,  'taxable' => false],
            ['code' => 'INTERNET',    'name' => 'Internet Allowance',     'type' => 'earning',   'calc' => 'fixed',      'seq' => 70,  'taxable' => false],
            ['code' => 'OVERTIME',    'name' => 'Overtime Pay',           'type' => 'earning',   'calc' => 'fixed',      'seq' => 80,  'taxable' => true, 'recurring' => false, 'gl' => '6021'],
            ['code' => 'COMMISSION',  'name' => 'Commission',             'type' => 'earning',   'calc' => 'fixed',      'seq' => 90,  'taxable' => true, 'recurring' => false],
            ['code' => 'PERF_BONUS',  'name' => 'Performance Bonus',      'type' => 'earning',   'calc' => 'fixed',      'seq' => 100, 'taxable' => true, 'recurring' => false, 'gl' => '6022'],
            ['code' => 'FESTIVAL',    'name' => 'Festival Bonus',         'type' => 'earning',   'calc' => 'fixed',      'seq' => 110, 'taxable' => true, 'recurring' => false, 'gl' => '6023'],

            // ── DEDUCTIONS ────────────────────────────────────────────────────
            ['code' => 'TAX',         'name' => 'Income Tax (TDS)',        'type' => 'deduction', 'calc' => 'percentage', 'of' => 'BASIC', 'val' => 0, 'seq' => 10, 'taxable' => false, 'gl' => '2031'],
            ['code' => 'PF',          'name' => 'Provident Fund',          'type' => 'deduction', 'calc' => 'percentage', 'of' => 'BASIC', 'val' => 0, 'seq' => 20, 'taxable' => false, 'gl' => '2032'],
            ['code' => 'INSURANCE',   'name' => 'Insurance Premium',       'type' => 'deduction', 'calc' => 'fixed',      'seq' => 30, 'taxable' => false, 'recurring' => false],
            ['code' => 'LATE',        'name' => 'Late Deduction',          'type' => 'deduction', 'calc' => 'fixed',      'seq' => 40, 'taxable' => false, 'recurring' => false],
            ['code' => 'ABSENT',      'name' => 'Absent Day Deduction',    'type' => 'deduction', 'calc' => 'formula',    'formula' => 'BASIC / working_days * absent_days', 'seq' => 50, 'taxable' => false, 'recurring' => false],
            ['code' => 'LOAN',        'name' => 'Loan Recovery',           'type' => 'deduction', 'calc' => 'fixed',      'seq' => 60, 'taxable' => false, 'gl' => '1150'],
            ['code' => 'FINE',        'name' => 'Fine / Penalty',          'type' => 'deduction', 'calc' => 'fixed',      'seq' => 70, 'taxable' => false, 'recurring' => false],
        ];

        foreach ($components as $comp) {
            PayrollComponent::withoutGlobalScopes()->updateOrCreate(
                ['shop_id' => null, 'code' => $comp['code']],
                [
                    'shop_id'         => null,
                    'name'            => $comp['name'],
                    'code'            => $comp['code'],
                    'component_type'  => $comp['type'],
                    'calculation_type'=> $comp['calc'],
                    'default_value'   => $comp['val'] ?? 0,
                    'percentage_of'   => $comp['of'] ?? null,
                    'formula'         => $comp['formula'] ?? null,
                    'is_taxable'      => $comp['taxable'],
                    'is_recurring'    => $comp['recurring'] ?? true,
                    'is_system'       => true,
                    'affects_gross'   => $comp['type'] === 'earning',
                    'sequence'        => $comp['seq'],
                    'gl_account_code' => $comp['gl'] ?? null,
                    'is_active'       => true,
                ]
            );
        }
    }
}