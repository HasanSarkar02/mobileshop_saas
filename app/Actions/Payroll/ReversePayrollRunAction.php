<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Enums\PayrollRunStatus;
use App\Enums\PayrollSlipStatus;
use App\Models\PayrollAuditLog;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class ReversePayrollRunAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(PayrollRun $run, string $reason, User $actor): PayrollRun
    {
        if ($run->status !== PayrollRunStatus::Approved->value &&
            $run->status->value !== 'approved') {
            throw new \RuntimeException(
                "Only approved payroll runs can be reversed. " .
                "Current status: {$run->status->label()}. " .
                "If the run has been partially paid, reverse all payments first."
            );
        }

        // Ensure no outstanding payments
        $unpaidSlips = $run->slips()
            ->whereIn('status', ['partially_paid', 'ready_for_payment'])
            ->count();

        if ($unpaidSlips > 0) {
            throw new \RuntimeException(
                "Cannot reverse payroll run: {$unpaidSlips} slips still have payments pending. " .
                "Reverse all individual payments first, then reverse the run."
            );
        }

        if (strlen(trim($reason)) < 10) {
            throw new \RuntimeException(
                "Please provide a meaningful reversal reason (minimum 10 characters)."
            );
        }

        $shop = $run->shop()->withoutGlobalScopes()->findOrFail($run->shop_id);

        // Check today is not in locked period
        if ($shop->books_locked_through &&
            now()->toDateString() <= $shop->books_locked_through) {
            throw new \RuntimeException(
                "Cannot post reversal: today's date falls within a locked accounting period."
            );
        }

        return DB::transaction(function () use ($run, $reason, $actor, $shop) {
            // Reverse the approval journal entry
            $originalEntry = $run->journalEntry;
            if (! $originalEntry) {
                throw new \RuntimeException("Original approval journal entry not found.");
            }

            $reversalEntry = $this->accounting->reverseEntry(
                $originalEntry,
                "Reversal of payroll {$run->run_number} — {$reason}",
                $actor,
            );

            // Update run
            $run->update([
                'status'                    => PayrollRunStatus::Reversed->value,
                'reversed_by'               => $actor->id,
                'reversed_at'               => now(),
                'reversal_reason'           => $reason,
                'reversal_journal_entry_id' => $reversalEntry->id,
            ]);

            // Update all slips
            $run->slips()->update(['status' => PayrollSlipStatus::Reversed->value]);

            PayrollAuditLog::record(
                shopId:        $run->shop_id,
                referenceType: 'payroll_runs',
                referenceId:   $run->id,
                action:        PayrollAuditAction::Reversed,
                oldStatus:     'approved',
                newStatus:     PayrollRunStatus::Reversed->value,
                amount:        (float) $run->total_net_payable,
                reason:        $reason,
            );

            return $run->fresh();
        });
    }
}