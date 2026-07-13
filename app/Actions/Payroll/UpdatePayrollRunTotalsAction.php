<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;

/**
 * Single-responsibility action: recalculate and persist all aggregate
 * totals on a PayrollRun from its slips' current state.
 *
 * Called by:
 *   - ProcessPayrollPaymentAction  (after payment recorded)
 *   - ReversePayrollPaymentAction  (after payment reversed)
 *
 * Never call another Action from here. Never touch journals.
 * Only reads slips, writes run totals + status.
 */
class UpdatePayrollRunTotalsAction
{
    public function execute(PayrollRun $run): PayrollRun
    {
        // Always reload fresh slip data — never trust stale in-memory state
        $run->loadMissing('slips');
        $slips = $run->slips()->get();

        $totalPaid = (float) $slips->sum('total_paid');

        $newStatus = $this->resolveRunStatus($run, $slips);

        $run->update([
            'total_paid' => $totalPaid,
            'status'     => $newStatus,
        ]);

        return $run->fresh();
    }

    private function resolveRunStatus(
        PayrollRun                           $run,
        \Illuminate\Database\Eloquent\Collection $slips,
    ): string {
        // Terminal states — never change via payment updates
        if (in_array($run->status->value, [
            PayrollRunStatus::Cancelled->value,
            PayrollRunStatus::Reversed->value,
        ])) {
            return $run->status->value;
        }

        // Run must be in an approvable payment state to auto-transition
        if (! $run->status->canAcceptPayments()) {
            return $run->status->value;
        }

        $total = $slips->count();

        if ($total === 0) {
            return $run->status->value;
        }

        $paidCount = $slips->filter(
            fn ($s) => $s->status->value === 'paid'
        )->count();

        $anyProgress = $slips->filter(fn ($s) => in_array($s->status->value, [
            'paid', 'partially_paid',
        ]))->count();

        return match (true) {
            $paidCount === $total => PayrollRunStatus::Paid->value,
            $anyProgress > 0     => PayrollRunStatus::PartiallyPaid->value,
            default              => PayrollRunStatus::ProcessingPayment->value,
        };
    }
}