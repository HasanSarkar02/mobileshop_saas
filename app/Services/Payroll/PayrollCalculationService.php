<?php

namespace App\Services\Payroll;

use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollComponent;
use App\Models\PayrollLoan;
use App\Models\PayrollRun;
use App\Models\User;

class PayrollCalculationService
{
    public function __construct(
        private readonly FormulaEvaluator $evaluator,
    ) {}

    /**
     * Calculate payroll for a single employee for a given run.
     * Returns array ready to create payroll_slips + payroll_slip_components.
     */
    public function calculateSlip(User $employee, PayrollRun $run): array
    {
        $structure = EmployeeSalaryStructure::withoutGlobalScopes()
            ->where('user_id', $employee->id)
            ->where('shop_id', $employee->shop_id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $run->period_to)
            ->where(function ($q) use ($run) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $run->period_from);
            })
            ->latest('effective_from')
            ->first();

        if (! $structure) {
            throw new \RuntimeException(
                "No active salary structure found for employee: {$employee->name}"
            );
        }

        // Load policy components using direct DB query (avoids pivot FK auto-guess)
        $policyComponents = collect(
            \Illuminate\Support\Facades\DB::table('payroll_policy_components as ppc')
                ->join('payroll_components as pc', 'pc.id', '=', 'ppc.component_id')
                ->where('ppc.policy_id', $structure->policy_id)
                ->where('pc.is_active', true)
                ->select([
                    'pc.id', 'pc.code', 'pc.name',
                    'pc.component_type', 'pc.is_taxable',
                    'pc.gl_account_code', 'pc.sequence as comp_sequence',
                    'ppc.calculation_type as pivot_calc_type',
                    'ppc.default_value as pivot_value',
                    'ppc.percentage_of as pivot_pct_of',
                    'ppc.formula as pivot_formula',
                    'ppc.sequence as pivot_sequence',
                ])
                ->orderBy('ppc.sequence')
                ->get()
        );

        // Employee-level overrides
        $employeeOverrides = \App\Models\EmployeeSalaryComponent::withoutGlobalScopes()
            ->where('salary_structure_id', $structure->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('component_id');

        // Build component value map for formula resolution
        $componentValues = [
            'working_days' => $structure->monthly_working_days,
            'absent_days'  => 0,   // overridden when attendance integrated
            'days_worked'  => $structure->monthly_working_days,
            'overtime_hours' => 0,
        ];

        $earnings   = [];
        $deductions = [];

        // ── First pass: earnings (needed as base for percentage deductions) ──
        foreach ($policyComponents as $row) {
            if ($row->component_type !== 'earning') continue;

            $override = $employeeOverrides->get($row->id);
            $calcType = $override?->calculation_type ?? $row->pivot_calc_type;
            $value    = (float) ($override?->value ?? $row->pivot_value ?? 0);
            $pctOf    = $override?->percentage_of ?? $row->pivot_pct_of;
            $formula  = $override?->formula ?? $row->pivot_formula;

            $computed = $this->computeValue($calcType, $value, $pctOf, $formula, $componentValues);

            $componentValues[$row->code] = $computed;

            $earnings[] = [
                'component' => (object) [
                    'id'       => $row->id,
                    'name'     => $row->name,
                    'code'     => $row->code,
                    'sequence' => $row->pivot_sequence,
                    'is_taxable' => (bool) $row->is_taxable,
                ],
                'computed_value'    => $computed,
                'calculation_type'  => $calcType,
                'calculation_basis' => $this->buildBasisDescription($calcType, $value, $pctOf, $computed),
                'formula_used'      => $calcType === 'formula' ? $formula : null,
            ];
        }

        $grossEarnings            = array_sum(array_column($earnings, 'computed_value'));
        $componentValues['GROSS'] = $grossEarnings;

        // ── Auto loan deductions ───────────────────────────────────────────
        $activeLoans = \App\Models\PayrollLoan::withoutGlobalScopes()
            ->where('user_id', $employee->id)
            ->where('shop_id', $employee->shop_id)
            ->where('status', 'active')
            ->get();

        $loanDeductionTotal = 0.0;
        $loanRecoveries     = [];

        foreach ($activeLoans as $loan) {
            $recovery = min((float) $loan->monthly_deduction, (float) $loan->outstanding_balance);
            if ($recovery > 0) {
                $loanDeductionTotal += $recovery;
                $loanRecoveries[]    = [
                    'loan'          => $loan,
                    'amount'        => $recovery,
                    'balance_after' => max(0, (float) $loan->outstanding_balance - $recovery),
                ];
            }
        }

        // ── Second pass: deductions ────────────────────────────────────────
        $loanComponentRow = null;

        foreach ($policyComponents as $row) {
            if ($row->component_type !== 'deduction') continue;
            if ($row->code === 'LOAN') {
                $loanComponentRow = $row;
                continue;
            }

            $override = $employeeOverrides->get($row->id);
            $calcType = $override?->calculation_type ?? $row->pivot_calc_type;
            $value    = (float) ($override?->value ?? $row->pivot_value ?? 0);
            $pctOf    = $override?->percentage_of ?? $row->pivot_pct_of;
            $formula  = $override?->formula ?? $row->pivot_formula;

            $computed = $this->computeValue($calcType, $value, $pctOf, $formula, $componentValues);

            if ($computed <= 0) continue;

            $deductions[] = [
                'component' => (object) [
                    'id'       => $row->id,
                    'name'     => $row->name,
                    'code'     => $row->code,
                    'sequence' => $row->pivot_sequence,
                    'is_taxable' => false,
                ],
                'computed_value'    => $computed,
                'calculation_type'  => $calcType,
                'calculation_basis' => $this->buildBasisDescription($calcType, $value, $pctOf, $computed),
                'formula_used'      => $calcType === 'formula' ? $formula : null,
            ];
        }

        // Add loan deduction as a single combined line
        if ($loanDeductionTotal > 0) {
            // Use a LOAN component object from the global seeded component
            $loanComp = \App\Models\PayrollComponent::withoutGlobalScopes()
                ->whereNull('shop_id')
                ->where('code', 'LOAN')
                ->first();

            if ($loanComp) {
                $deductions[] = [
                    'component' => (object) [
                        'id'       => $loanComp->id,
                        'name'     => $loanComp->name,
                        'code'     => 'LOAN',
                        'sequence' => $loanComp->sequence,
                        'is_taxable' => false,
                    ],
                    'computed_value'    => $loanDeductionTotal,
                    'calculation_type'  => 'fixed',
                    'calculation_basis' => 'Auto-computed from ' . count($loanRecoveries) . ' active loan(s)',
                    'formula_used'      => null,
                ];
            }
        }

        $totalDeductions = array_sum(array_column($deductions, 'computed_value'));
        $netPayable      = max(0, $grossEarnings - $totalDeductions);

        return [
            'slip' => [
                'user_id'            => $employee->id,
                'employee_name'      => $employee->name,
                'designation'        => $structure->designation,
                'department_name'    => $structure->department?->name,
                'employment_type'    => $structure->employment_type->value,
                'working_days'       => $structure->monthly_working_days,
                'days_worked'        => $structure->monthly_working_days,
                'leaves_paid'        => 0,
                'leaves_unpaid'      => 0,
                'absent_days'        => 0,
                'overtime_hours'     => 0,
                'gross_earnings'     => $grossEarnings,
                'total_deductions'   => $totalDeductions,
                'net_payable'        => $netPayable,
                'total_paid'         => 0,
                'balance_payable'    => $netPayable,
                'payment_account_id' => $structure->payment_account_id,
                'payment_method'     => $structure->payment_method,
            ],
            'earnings'        => $earnings,
            'deductions'      => $deductions,
            'loan_recoveries' => $loanRecoveries,
        ];
    }

    private function computeValue(
        string  $calculationType,
        float   $value,
        ?string $percentageOf,
        ?string $formula,
        array   &$componentValues,
    ): float {
        return match ($calculationType) {
            'fixed'      => $value,
            'percentage' => $percentageOf
                ? (($componentValues[strtoupper($percentageOf)] ?? 0) * $value / 100)
                : 0,
            'formula'    => $formula
                ? $this->evaluator->evaluate($formula, $componentValues)
                : 0,
            default      => $value,
        };
    }

    private function buildBasisDescription(
        string  $calcType,
        float   $value,
        ?string $percentageOf,
        float   $computed,
    ): string {
        return match ($calcType) {
            'fixed'      => 'Fixed: ৳' . number_format($computed, 2),
            'percentage' => number_format($value, 2) . '% of ' . strtoupper($percentageOf ?? '') .
                            ' = ৳' . number_format($computed, 2),
            'formula'    => 'Formula result: ৳' . number_format($computed, 2),
            default      => '৳' . number_format($computed, 2),
        };
    }
}