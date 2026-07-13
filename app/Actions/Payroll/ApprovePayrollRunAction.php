<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Enums\PayrollRunStatus;
use App\Enums\PayrollSlipStatus;
use App\Models\Account;
use App\Models\PayrollAuditLog;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class ApprovePayrollRunAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(PayrollRun $run, User $actor): PayrollRun
    {
        if (! $run->status->canBeApproved()) {
            throw new \RuntimeException(
                "Cannot approve a payroll run with status: {$run->status->label()}. " .
                "Only 'Under Review' runs can be approved."
            );
        }

        $shop = $run->shop()->withoutGlobalScopes()->findOrFail($run->shop_id);

        // Period lock check
        if ($shop->books_locked_through &&
            $run->period_to->toDateString() <= $shop->books_locked_through) {
            throw new \RuntimeException(
                "Cannot approve: payroll period is within a locked accounting period."
            );
        }

        if ($run->total_net_payable <= 0) {
            throw new \RuntimeException(
                "Cannot approve: total net payable is zero or negative."
            );
        }

        return DB::transaction(function () use ($run, $actor, $shop) {
            // Build journal lines
            $lines = $this->buildJournalLines($run, $shop->id);

            $description = "Payroll Approval — {$run->monthName()} — {$run->total_employees} employees";

            $journalEntry = $this->accounting->postEntry(
                shop:        $shop,
                description: $description,
                lines:       $lines,
                entryDate:   $run->period_to->toDateTime(),
                reference:   $run,
                branchId:    $run->branch_id,
                actor:       $actor,
            );

            // Update run status
            $run->update([
                'status'           => PayrollRunStatus::Approved->value,
                'approved_by'      => $actor->id,
                'approved_at'      => now(),
                'journal_entry_id' => $journalEntry->id,
            ]);

            // Update all slips to approved → ready_for_payment
            $run->slips()->where('status', PayrollSlipStatus::Draft->value)->update([
                'status' => PayrollSlipStatus::ReadyForPayment->value,
            ]);

            PayrollAuditLog::record(
                shopId:        $run->shop_id,
                referenceType: 'payroll_runs',
                referenceId:   $run->id,
                action:        PayrollAuditAction::Approved,
                oldStatus:     PayrollRunStatus::UnderReview->value,
                newStatus:     PayrollRunStatus::Approved->value,
                amount:        (float) $run->total_net_payable,
            );

            return $run->fresh();
        });
    }

    private function buildJournalLines(PayrollRun $run, int $shopId): array
    {
        $run->load(['slips.earnings', 'slips.deductions']);

        $gl = fn (string $code) => $this->glByCode($shopId, $code);

        // Aggregate totals per GL account
        $basicTotal    = 0.0; // → 6020
        $overtimeTotal = 0.0; // → 6021
        $bonusTotal    = 0.0; // → 6022
        $festivalTotal = 0.0; // → 6023

        $taxTotal      = 0.0; // → 2031
        $pfTotal       = 0.0; // → 2032
        $loanTotal     = 0.0; // → 1150 (Cr reduces asset)
        $otherDedTotal = 0.0; // → 2033

        $netPayable    = (float) $run->total_net_payable; // → 2030

        foreach ($run->slips as $slip) {
            foreach ($slip->earnings as $earning) {
                match ($earning->component_code) {
                    'OVERTIME'   => $overtimeTotal += (float) $earning->computed_value,
                    'PERF_BONUS' => $bonusTotal    += (float) $earning->computed_value,
                    'FESTIVAL'   => $festivalTotal += (float) $earning->computed_value,
                    default      => $basicTotal    += (float) $earning->computed_value,
                };
            }

            foreach ($slip->deductions as $deduction) {
                match ($deduction->component_code) {
                    'TAX'  => $taxTotal      += (float) $deduction->computed_value,
                    'PF'   => $pfTotal        += (float) $deduction->computed_value,
                    'LOAN' => $loanTotal      += (float) $deduction->computed_value,
                    default=> $otherDedTotal  += (float) $deduction->computed_value,
                };
            }
        }

        $lines = [];

        // Dr Expense accounts
        if ($basicTotal > 0) {
            $lines[] = ['account_id' => $gl('6020')->id, 'debit' => $basicTotal,
                        'description' => "Salary expense — {$run->monthName()}"];
        }
        if ($overtimeTotal > 0) {
            $lines[] = ['account_id' => $gl('6021')->id, 'debit' => $overtimeTotal,
                        'description' => "Overtime expense — {$run->monthName()}"];
        }
        if ($bonusTotal > 0) {
            $lines[] = ['account_id' => $gl('6022')->id, 'debit' => $bonusTotal,
                        'description' => "Bonus expense — {$run->monthName()}"];
        }
        if ($festivalTotal > 0) {
            $lines[] = ['account_id' => $gl('6023')->id, 'debit' => $festivalTotal,
                        'description' => "Festival bonus expense — {$run->monthName()}"];
        }

        // Cr Liability accounts (deductions)
        if ($taxTotal > 0) {
            $lines[] = ['account_id' => $gl('2031')->id, 'credit' => $taxTotal,
                        'description' => "Tax withheld — {$run->monthName()}"];
        }
        if ($pfTotal > 0) {
            $lines[] = ['account_id' => $gl('2032')->id, 'credit' => $pfTotal,
                        'description' => "PF payable — {$run->monthName()}"];
        }
        if ($loanTotal > 0) {
            $lines[] = ['account_id' => $gl('1150')->id, 'credit' => $loanTotal,
                        'description' => "Loan recovery — {$run->monthName()}"];
        }
        if ($otherDedTotal > 0) {
            $lines[] = ['account_id' => $gl('2033')->id, 'credit' => $otherDedTotal,
                        'description' => "Other deductions — {$run->monthName()}"];
        }

        // Cr Salary Payable (2030) — net amount employees actually receive
        $lines[] = ['account_id' => $gl('2030')->id, 'credit' => $netPayable,
                    'description' => "Salary payable — {$run->monthName()} — {$run->total_employees} employees"];

        return $lines;
    }

    private function glByCode(int $shopId, string $code): \App\Models\Account
    {
        $account = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('code', $code)
            ->first();

        if (! $account) {
            throw new \RuntimeException(
                "GL account '{$code}' not found. Run: php artisan payroll:provision-gl-accounts"
            );
        }

        return $account;
    }
}