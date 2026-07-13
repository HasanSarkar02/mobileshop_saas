<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Enums\PayrollRunStatus;
use App\Enums\PayrollSlipStatus;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollAuditLog;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use App\Models\PayrollSlipComponent;
use App\Models\Shop;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneratePayrollRunAction
{
    public function __construct(
        private readonly PayrollCalculationService $calculator,
    ) {}

    public function execute(Shop $shop, array $data, User $actor): array
    {
        $year         = (int) $data['year'];
        $month        = (int) $data['month'];
        $branchId     = $data['branch_id'] ? (int) $data['branch_id'] : null;
        $departmentId = $data['department_id'] ? (int) $data['department_id'] : null;
        $employmentType = $data['employment_type'] ?: null;

        // ── Guard: check for existing non-cancelled run ─────────────────────
        $existing = PayrollRun::where('shop_id', $shop->id)
            ->where('year', $year)
            ->where('month', $month)
            ->when($branchId,     fn ($q) => $q->where('branch_id', $branchId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($employmentType, fn ($q) => $q->where('employment_type', $employmentType))
            ->whereNotIn('status', ['cancelled', 'reversed'])
            ->first();

        if ($existing) {
            throw new \RuntimeException(
                "A payroll run for this scope already exists: {$existing->run_number} " .
                "(Status: {$existing->status->label()}). Cancel it first to regenerate."
            );
        }

        // ── Build employee list ──────────────────────────────────────────────
        $periodFrom = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $periodTo   = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $employees = $this->resolveEmployees(
            $shop, $branchId, $departmentId, $employmentType, $periodTo
        );

        if ($employees->isEmpty()) {
            throw new \RuntimeException(
                "No employees found with active salary structures for the selected scope and period."
            );
        }

        // ── Generate run + slips in one transaction ──────────────────────────
        return DB::transaction(function () use (
            $shop, $year, $month, $periodFrom, $periodTo,
            $branchId, $departmentId, $employmentType,
            $employees, $actor, $data
        ) {
            $runNumber = $this->nextRunNumber($shop, $year, $month);

            $run = PayrollRun::create([
                'shop_id'         => $shop->id,
                'run_number'      => $runNumber,
                'year'            => $year,
                'month'           => $month,
                'period_from'     => $periodFrom,
                'period_to'       => $periodTo,
                'branch_id'       => $branchId,
                'department_id'   => $departmentId,
                'employment_type' => $employmentType,
                'status'          => PayrollRunStatus::Draft->value,
                'description'     => $data['description'] ?? null,
                'generated_by'    => $actor->id,
            ]);

            $warnings = [];
            $slipCount = 0;
            $totalGross = 0.0;
            $totalDeductions = 0.0;
            $totalNet = 0.0;

            foreach ($employees as $employee) {
                // Check if resigned
                if ($employee->employment_status === 'resigned' ||
                    $employee->employment_status === 'terminated') {
                    $warnings[] = "⚠ {$employee->name} — Employment status: " .
                                  $employee->employment_status . ". Included with warning.";
                }

                try {
                    $calculated = $this->calculator->calculateSlip($employee, $run);

                    $slip = PayrollSlip::create([
                        'shop_id'          => $shop->id,
                        'payroll_run_id'   => $run->id,
                        'status'           => PayrollSlipStatus::Draft->value,
                        ...$calculated['slip'],
                    ]);

                    // Create slip components (IMMUTABLE snapshot)
                    foreach ($calculated['earnings'] as $seq => $earning) {
                        PayrollSlipComponent::create([
                            'slip_id'          => $slip->id,
                            'component_id'     => $earning['component']->id,
                            'component_name'   => $earning['component']->name,
                            'component_code'   => $earning['component']->code,
                            'component_type'   => 'earning',
                            'is_taxable'       => $earning['component']->is_taxable,
                            'sequence'         => $earning['component']->sequence,
                            'calculation_type' => $earning['calculation_type'],
                            'calculation_basis'=> $earning['calculation_basis'],
                            'formula_used'     => $earning['formula_used'],
                            'computed_value'   => $earning['computed_value'],
                        ]);
                    }

                    foreach ($calculated['deductions'] as $deduction) {
                        PayrollSlipComponent::create([
                            'slip_id'          => $slip->id,
                            'component_id'     => $deduction['component']->id,
                            'component_name'   => $deduction['component']->name,
                            'component_code'   => $deduction['component']->code,
                            'component_type'   => 'deduction',
                            'is_taxable'       => false,
                            'sequence'         => $deduction['component']->sequence ?? 100,
                            'calculation_type' => $deduction['calculation_type'],
                            'calculation_basis'=> $deduction['calculation_basis'],
                            'formula_used'     => $deduction['formula_used'],
                            'computed_value'   => $deduction['computed_value'],
                        ]);
                    }

                    $slipCount++;
                    $totalGross      += (float) $calculated['slip']['gross_earnings'];
                    $totalDeductions += (float) $calculated['slip']['total_deductions'];
                    $totalNet        += (float) $calculated['slip']['net_payable'];

                } catch (\Exception $e) {
                    $warnings[] = "✗ {$employee->name} — Skipped: {$e->getMessage()}";
                }
            }

            // Update run totals
            $run->update([
                'total_employees'      => $slipCount,
                'total_gross_earnings' => $totalGross,
                'total_deductions'     => $totalDeductions,
                'total_net_payable'    => $totalNet,
                'total_paid'           => 0,
            ]);

            // Audit log
            PayrollAuditLog::record(
                shopId:        $shop->id,
                referenceType: 'payroll_runs',
                referenceId:   $run->id,
                action:        PayrollAuditAction::Generated,
                newStatus:     PayrollRunStatus::Draft->value,
                metadata:      [
                    'employee_count' => $slipCount,
                    'total_net'      => $totalNet,
                    'warnings'       => $warnings,
                ],
            );

            return [
                'run'      => $run->fresh(),
                'warnings' => $warnings,
            ];
        });
    }

    private function resolveEmployees(
        Shop    $shop,
        ?int    $branchId,
        ?int    $departmentId,
        ?string $employmentType,
        string  $asOfDate,
    ): Collection {
        return User::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->where('user_type', 'employee')
            ->whereHas('employeeSalaryStructures', function ($q) use ($departmentId, $employmentType, $asOfDate) {
                $q->where('is_active', true)
                  ->where('effective_from', '<=', $asOfDate)
                  ->where(fn ($sq) =>
                      $sq->whereNull('effective_to')->orWhere('effective_to', '>=', $asOfDate)
                  );

                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }

                if ($employmentType) {
                    $q->where('employment_type', $employmentType);
                }
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();
    }

    private function nextRunNumber(Shop $shop, int $year, int $month): string
    {
        $monthPad = str_pad($month, 2, '0', STR_PAD_LEFT);
        $key      = "payroll_{$year}_{$monthPad}";

        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, $key]
        );

        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', $key)
            ->value('current_value');

        return sprintf('PRN-%d-%s-%03d', $year, $monthPad, $seq);
    }
}