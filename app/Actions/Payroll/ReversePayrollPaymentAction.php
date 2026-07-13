<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Enums\PayrollSlipStatus;
use App\Models\PayrollAuditLog;
use App\Models\PayrollLoan;
use App\Models\PayrollLoanRecovery;
use App\Models\PayrollPayment;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class ReversePayrollPaymentAction
{
    public function __construct(
        private readonly AccountingService           $accounting,
        private readonly UpdatePayrollRunTotalsAction $updateRunTotals,
    ) {}

    public function execute(PayrollPayment $payment, string $reason, User $actor): PayrollPayment
    {
        // ── Guards ─────────────────────────────────────────────────────────
        if ($payment->status !== 'paid') {
            throw new \RuntimeException(
                "Only active payments can be reversed. " .
                "This payment has status: {$payment->status}."
            );
        }

        if (strlen(trim($reason)) < 5) {
            throw new \RuntimeException(
                "Please provide a meaningful reversal reason (minimum 5 characters)."
            );
        }

        $originalEntry = $payment->journalEntry;
        if (! $originalEntry) {
            throw new \RuntimeException(
                "Original journal entry not found for payment {$payment->payment_number}. " .
                "Cannot reverse without accounting evidence."
            );
        }

        return DB::transaction(function () use ($payment, $reason, $actor, $originalEntry) {

            $slip = $payment->slip;
            $run  = $slip->payrollRun;
            $shop = $run->shop()->withoutGlobalScopes()->findOrFail($slip->shop_id);

            // ── Reversal journal posted TODAY (period-lock compatible) ──────
            $reversalEntry = $this->accounting->reverseEntry(
                $originalEntry,
                "Reversal: {$payment->payment_number} — {$reason}",
                $actor,
            );

            // ── Mark payment as reversed ───────────────────────────────────
            $payment->update([
                'status'                    => 'reversed',
                'reversal_journal_entry_id' => $reversalEntry->id,
                'reversed_by'               => $actor->id,
                'reversed_at'               => now(),
                'reversal_reason'           => $reason,
            ]);

            // ── Update slip: restore balance ───────────────────────────────
            $newPaid    = max(0, (float) $slip->total_paid - (float) $payment->amount);
            $newBalance = max(0, (float) $slip->net_payable - $newPaid);

            $newSlipStatus = $newPaid > 0.005
                ? PayrollSlipStatus::PartiallyPaid->value
                : PayrollSlipStatus::ReadyForPayment->value;

            $slip->update([
                'total_paid'      => $newPaid,
                'balance_payable' => $newBalance,
                'status'          => $newSlipStatus,
            ]);

            // ── Restore loan balances for recoveries tied to this slip ─────
            $this->restoreLoanRecoveries($slip);

            // ── Delegate run total recalculation ──────────────────────────
            // Same action used by ProcessPayrollPaymentAction — consistent logic
            $this->updateRunTotals->execute($run);

            // ── Audit ──────────────────────────────────────────────────────
            PayrollAuditLog::record(
                shopId:        $shop->id,
                referenceType: 'payroll_slips',
                referenceId:   $slip->id,
                action:        PayrollAuditAction::PaymentReversed,
                oldStatus:     PayrollSlipStatus::Paid->value,
                newStatus:     $newSlipStatus,
                amount:        (float) $payment->amount,
                reason:        $reason,
                metadata:      [
                    'payment_number'      => $payment->payment_number,
                    'reversal_journal_id' => $reversalEntry->id,
                ],
            );

            return $payment->fresh(['reversedBy', 'journalEntry']);
        });
    }

    /**
     * Restore outstanding_balance on every loan that had a recovery
     * recorded against this slip. Deletes the recovery records so they
     * can be re-recorded on the next payment.
     *
     * Called only from ReversePayrollPaymentAction — private by design.
     */
    private function restoreLoanRecoveries(\App\Models\PayrollSlip $slip): void
    {
        $recoveries = PayrollLoanRecovery::where('slip_id', $slip->id)
            ->with('loan')
            ->get();

        if ($recoveries->isEmpty()) {
            return;
        }

        foreach ($recoveries as $recovery) {
            $loan = PayrollLoan::withoutGlobalScopes()
                ->where('id', $recovery->loan_id)
                ->lockForUpdate()
                ->first();

            if (! $loan) continue;

            $restoredBalance = (float) $loan->outstanding_balance + (float) $recovery->amount_recovered;

            $loan->update([
                'outstanding_balance' => $restoredBalance,
                'status'              => 'active',  // reactivate if it was fully_recovered
            ]);

            PayrollAuditLog::record(
                shopId:        $slip->shop_id,
                referenceType: 'payroll_loans',
                referenceId:   $loan->id,
                action:        PayrollAuditAction::LoanRecovered,
                amount:        -(float) $recovery->amount_recovered,
                metadata:      [
                    'restored_balance' => $restoredBalance,
                    'reason'           => 'Payment reversal — recovery undone',
                ],
            );

            // Delete the recovery record so it can be re-created on next payment
            $recovery->delete();
        }
    }
}