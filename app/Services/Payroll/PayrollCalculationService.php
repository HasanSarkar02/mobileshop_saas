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
            ->with(['policy.components', 'activeComponents.component'])
            ->latest('effective_from')
            ->first();

        if (! $structure) {
            throw new \RuntimeException(
                "No active salary structure found for employee: {$employee->name}"
            );
        }

        // Build component value map (code → value) for formula resolution
        $resolvedComponents = $structure->resolvedComponents();
        $componentValues    = ['working_days' => $structure->monthly_working_days];

        // First pass — calculate all earnings to build the value map
        $earnings    = [];
        $deductions  = [];

        foreach ($resolvedComponents as $resolved) {
            $component = $resolved->component;

            if ($component->component_type->value !== 'earning') continue;

            $value = $this->computeValue(
                $resolved->calculation_type,
                $resolved->value,
                $resolved->percentage_of,
                $resolved->formula,
                $componentValues,
            );

            $componentValues[$component->code] = $value;

            $earnings[] = [
                'component'         => $component,
                'computed_value'    => $value,
                'calculation_type'  => $resolved->calculation_type,
                'calculation_basis' => $this->buildBasisDescription(
                    $resolved->calculation_type,
                    $resolved->value,
                    $resolved->percentage_of,
                    $value,
                ),
                'formula_used'      => $resolved->formula,
            ];
        }

        // Total gross earnings
        $grossEarnings = array_sum(array_column($earnings, 'computed_value'));
        $componentValues['GROSS'] = $grossEarnings;

        // Auto-add active loan deductions
        $activeLoans = PayrollLoan::withoutGlobalScopes()
            ->where('user_id', $employee->id)
            ->where('shop_id', $employee->shop_id)
            ->where('status', 'active')
            ->get();

        $loanDeductionTotal = 0.0;
        $loanRecoveries     = [];

        foreach ($activeLoans as $loan) {
            $recovery = min(
                (float) $loan->monthly_deduction,
                (float) $loan->outstanding_balance
            );

            if ($recovery > 0) {
                $loanDeductionTotal  += $recovery;
                $loanRecoveries[]     = [
                    'loan'           => $loan,
                    'amount'         => $recovery,
                    'balance_after'  => max(0, (float) $loan->outstanding_balance - $recovery),
                ];
            }
        }

        // Second pass — deductions
        $loanComponent = PayrollComponent::withoutGlobalScopes()
            ->whereNull('shop_id')
            ->where('code', 'LOAN')
            ->first();

        foreach ($resolvedComponents as $resolved) {
            $component = $resolved->component;

            if ($component->component_type->value !== 'deduction') continue;
            if ($component->code === 'LOAN') continue; // handled separately above

            $value = $this->computeValue(
                $resolved->calculation_type,
                $resolved->value,
                $resolved->percentage_of,
                $resolved->formula,
                $componentValues,
            );

            if ($value <= 0) continue;

            $deductions[] = [
                'component'         => $component,
                'computed_value'    => $value,
                'calculation_type'  => $resolved->calculation_type,
                'calculation_basis' => $this->buildBasisDescription(
                    $resolved->calculation_type,
                    $resolved->value,
                    $resolved->percentage_of,
                    $value,
                ),
                'formula_used'      => $resolved->formula,
            ];
        }

        // Add loan deduction as a single line
        if ($loanDeductionTotal > 0 && $loanComponent) {
            $deductions[] = [
                'component'        => $loanComponent,
                'computed_value'   => $loanDeductionTotal,
                'calculation_type' => 'fixed',
                'calculation_basis'=> 'Auto-computed from ' . count($loanRecoveries) . ' active loan(s)',
                'formula_used'     => null,
            ];
        }

        $totalDeductions = array_sum(array_column($deductions, 'computed_value'));
        $netPayable      = max(0, $grossEarnings - $totalDeductions);

        $dept = $structure->department;
        $emp  = $employee->employeeProfile ?? null;

        return [
            'slip' => [
                'user_id'            => $employee->id,
                'employee_name'      => $employee->name,
                'designation'        => $structure->designation ?? $emp?->designation,
                'department_name'    => $dept?->name,
                'employment_type'    => $structure->employment_type->value,
                'working_days'       => $structure->monthly_working_days,
                'days_worked'        => $structure->monthly_working_days, // override with actual attendance
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
            'earnings'       => $earnings,
            'deductions'     => $deductions,
            'loan_recoveries'=> $loanRecoveries,
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