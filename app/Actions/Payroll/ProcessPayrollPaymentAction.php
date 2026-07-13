<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Enums\PayrollSlipStatus;
use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\PayrollAuditLog;
use App\Models\PayrollLoan;
use App\Models\PayrollLoanRecovery;
use App\Models\PayrollPayment;
use App\Models\PayrollSlip;
use App\Models\User;
use App\Services\AccountBalanceChecker;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class ProcessPayrollPaymentAction
{
    public function __construct(
        private readonly AccountingService         $accounting,
        private readonly AccountBalanceChecker     $balanceChecker,
        private readonly UpdatePayrollRunTotalsAction $updateRunTotals,
    ) {}

    public function execute(PayrollSlip $slip, array $data, User $actor): PayrollPayment
    {
        // ── Guard: slip must be in a payable state ─────────────────────────
        if (! $slip->status->canAcceptPayment()) {
            throw new \RuntimeException(
                "Cannot process payment: slip status is '{$slip->status->label()}'. " .
                "Only 'Ready for Payment' or 'Partially Paid' slips can be paid."
            );
        }

        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new \RuntimeException("Payment amount must be greater than zero.");
        }

        // ── Guard: overpayment ─────────────────────────────────────────────
        if ($amount > ((float) $slip->balance_payable + 0.005)) {
            throw new \RuntimeException(
                "Payment ৳" . number_format($amount, 2) .
                " exceeds outstanding balance ৳" . number_format($slip->balance_payable, 2) . "."
            );
        }

        // ── Guard: payment account balance ─────────────────────────────────
        $balanceCheck = $this->balanceChecker->checkDebit(
            (int) $data['payment_account_id'],
            $amount
        );

        if (! $balanceCheck['allowed']) {
            throw new \RuntimeException($balanceCheck['message']);
        }

        // ── Guard: duplicate payment (same amount, same slip, same date) ───
        $duplicate = PayrollPayment::where('slip_id', $slip->id)
            ->where('status', 'paid')
            ->where('amount', $amount)
            ->whereDate('payment_date', $data['payment_date'])
            ->exists();

        if ($duplicate) {
            throw new \RuntimeException(
                "A payment of ৳" . number_format($amount, 2) .
                " was already recorded for this employee on " . $data['payment_date'] . ". " .
                "Check the payment history before proceeding."
            );
        }

        return DB::transaction(function () use ($slip, $data, $amount, $actor, $balanceCheck) {

            $shop = $slip->payrollRun->shop()
                ->withoutGlobalScopes()
                ->findOrFail($slip->shop_id);

            // ── Post journal: Dr Salary Payable / Cr Cash/Bank ────────────
            $payableGl = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('code', '2030')
                ->firstOrFail();

            $pa    = PaymentAccount::withoutGlobalScopes()->findOrFail($data['payment_account_id']);
            $payGl = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

            $paymentNumber = $this->nextPaymentNumber($shop);

            $journalEntry = $this->accounting->postEntry(
                shop:        $shop,
                description: "Salary payment — {$slip->employee_name} ({$paymentNumber})",
                lines: [
                    [
                        'account_id'  => $payableGl->id,
                        'debit'       => $amount,
                        'description' => "Salary paid — {$slip->employee_name}",
                    ],
                    [
                        'account_id'  => $payGl->id,
                        'credit'      => $amount,
                        'description' => "Paid via {$pa->name}",
                    ],
                ],
                entryDate: new \DateTime($data['payment_date']),
                reference: $slip,
                branchId:  $slip->payrollRun->branch_id,
                actor:     $actor,
            );

            // ── Create payment record ──────────────────────────────────────
            $payment = PayrollPayment::create([
                'shop_id'            => $shop->id,
                'payroll_run_id'     => $slip->payroll_run_id,
                'slip_id'            => $slip->id,
                'payment_number'     => $paymentNumber,
                'payment_account_id' => $data['payment_account_id'],
                'payment_method'     => $data['payment_method'] ?? 'cash',
                'amount'             => $amount,
                'payment_date'       => $data['payment_date'],
                'reference_number'   => $data['reference_number'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'status'             => 'paid',
                'journal_entry_id'   => $journalEntry->id,
                'created_by'         => $actor->id,
            ]);

            // ── Update slip totals + status ────────────────────────────────
            $newPaid    = (float) $slip->total_paid + $amount;
            $newBalance = max(0, (float) $slip->net_payable - $newPaid);
            $newStatus  = $newBalance <= 0.005
                ? PayrollSlipStatus::Paid->value
                : PayrollSlipStatus::PartiallyPaid->value;

            $slip->update([
                'total_paid'      => $newPaid,
                'balance_payable' => $newBalance,
                'status'          => $newStatus,
            ]);

            // ── Record loan recoveries on final payment ────────────────────
            if ($newBalance <= 0.005) {
                $this->recordLoanRecoveries($slip);
            }

            // ── Delegate run total recalculation ──────────────────────────
            // Single-responsibility: UpdatePayrollRunTotalsAction owns this logic
            $this->updateRunTotals->execute($slip->payrollRun);

            // ── Audit ──────────────────────────────────────────────────────
            PayrollAuditLog::record(
                shopId:        $shop->id,
                referenceType: 'payroll_slips',
                referenceId:   $slip->id,
                action:        PayrollAuditAction::PaymentMade,
                oldStatus:     $slip->status->value,
                newStatus:     $newStatus,
                amount:        $amount,
                metadata:      ['payment_number' => $paymentNumber],
            );

            if (isset($balanceCheck['warning'])) {
                session()->flash('balance_warning', $balanceCheck['warning']);
            }

            return $payment->fresh(['paymentAccount', 'journalEntry']);
        });
    }

    /**
     * Record automatic loan recoveries when a slip is fully paid.
     * Private because this is internal payroll logic, not a reusable operation.
     */
    private function recordLoanRecoveries(PayrollSlip $slip): void
    {
        $loanComponent = $slip->deductions()
            ->where('component_code', 'LOAN')
            ->first();

        if (! $loanComponent || (float) $loanComponent->computed_value <= 0) {
            return;
        }

        $activeLoans = PayrollLoan::withoutGlobalScopes()
            ->where('user_id', $slip->user_id)
            ->where('shop_id', $slip->shop_id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

        $remaining = (float) $loanComponent->computed_value;

        foreach ($activeLoans as $loan) {
            if ($remaining <= 0.005) break;

            $recovery     = min(
                (float) $loan->monthly_deduction,
                $remaining,
                (float) $loan->outstanding_balance
            );
            $balanceAfter = max(0, (float) $loan->outstanding_balance - $recovery);

            PayrollLoanRecovery::create([
                'loan_id'          => $loan->id,
                'slip_id'          => $slip->id,
                'amount_recovered' => $recovery,
                'balance_after'    => $balanceAfter,
                'recovery_date'    => now()->toDateString(),
            ]);

            $loan->update([
                'outstanding_balance' => $balanceAfter,
                'status'              => $balanceAfter <= 0.005 ? 'fully_recovered' : 'active',
            ]);

            $remaining -= $recovery;

            PayrollAuditLog::record(
                shopId:        $slip->shop_id,
                referenceType: 'payroll_loans',
                referenceId:   $loan->id,
                action:        PayrollAuditAction::LoanRecovered,
                amount:        $recovery,
                metadata:      ['balance_after' => $balanceAfter, 'slip_id' => $slip->id],
            );
        }
    }

    private function nextPaymentNumber(\App\Models\Shop $shop): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "payroll_pay_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "payroll_pay_{$year}")
            ->value('current_value');
        return sprintf('PPY-%s-%05d', $year, $seq);
    }
}