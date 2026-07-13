<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\PayrollAuditLog;
use App\Models\PayrollLoan;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountBalanceChecker;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class DisburseLoanAction
{
    public function __construct(
        private readonly AccountingService    $accounting,
        private readonly AccountBalanceChecker $balanceChecker,
    ) {}

    public function execute(Shop $shop, User $employee, array $data, User $actor): PayrollLoan
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new \RuntimeException("Loan amount must be greater than zero.");
        }

        if ((float) $data['monthly_deduction'] <= 0) {
            throw new \RuntimeException("Monthly deduction must be greater than zero.");
        }

        if ($data['monthly_deduction'] > $amount) {
            throw new \RuntimeException("Monthly deduction cannot exceed the total loan amount.");
        }

        // Balance check
        $check = $this->balanceChecker->checkDebit(
            (int) $data['payment_account_id'],
            $amount
        );

        if (! $check['allowed']) {
            throw new \RuntimeException($check['message']);
        }

        return DB::transaction(function () use ($shop, $employee, $data, $amount, $actor, $check) {
            $loanNumber = $this->nextLoanNumber($shop);

            $loan = PayrollLoan::create([
                'shop_id'                => $shop->id,
                'user_id'                => $employee->id,
                'loan_number'            => $loanNumber,
                'loan_type'              => $data['loan_type'] ?? 'advance',
                'total_amount'           => $amount,
                'outstanding_balance'    => $amount,
                'monthly_deduction'      => (float) $data['monthly_deduction'],
                'purpose'                => $data['purpose'] ?? null,
                'notes'                  => $data['notes'] ?? null,
                'status'                 => 'active',
                'disbursement_account_id'=> $data['payment_account_id'],
                'disbursement_date'      => $data['disbursement_date'],
                'approved_by'            => $actor->id,
                'approved_at'            => now(),
                'created_by'             => $actor->id,
            ]);

            // GL: Dr Salary Advance Receivable (1150) / Cr Cash/Bank
            $advanceGl = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('code', '1150')
                ->firstOrFail();

            $pa    = PaymentAccount::withoutGlobalScopes()->findOrFail($data['payment_account_id']);
            $payGl = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

            $journalEntry = $this->accounting->postEntry(
                shop:        $shop,
                description: "Employee loan disbursement — {$employee->name} ({$loanNumber})",
                lines: [
                    ['account_id' => $advanceGl->id, 'debit'  => $amount,
                     'description' => "Advance to {$employee->name}"],
                    ['account_id' => $payGl->id,     'credit' => $amount,
                     'description' => "Paid from {$pa->name}"],
                ],
                entryDate: new \DateTime($data['disbursement_date']),
                reference: $loan,
                branchId:  $employee->branch_id,
                actor:     $actor,
            );

            $loan->update(['disbursement_journal_entry_id' => $journalEntry->id]);

            PayrollAuditLog::record(
                shopId:        $shop->id,
                referenceType: 'payroll_loans',
                referenceId:   $loan->id,
                action:        PayrollAuditAction::LoanDisbursed,
                newStatus:     'active',
                amount:        $amount,
            );

            return $loan->fresh(['user', 'disbursementAccount']);
        });
    }

    private function nextLoanNumber(Shop $shop): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "payroll_loan_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "payroll_loan_{$year}")
            ->value('current_value');
        return sprintf('PLN-%s-%04d', $year, $seq);
    }
}