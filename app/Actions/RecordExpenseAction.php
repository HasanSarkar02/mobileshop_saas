<?php

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentAccount;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use App\Events\ExpensePendingApproval;
use Illuminate\Support\Facades\DB;

class RecordExpenseAction
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly \App\Services\AccountBalanceChecker $balanceChecker,
     ) {}

    public function execute(Shop $shop, array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($shop, $data, $actor) {

            $amount    = (float) $data['amount'];
            $threshold = (float) $shop->expense_approval_threshold;

            // Determine if auto-approval applies:
            // threshold = 0 means no threshold (always auto-approve)
            // threshold > 0 means amounts ABOVE threshold need manual approval
            $needsApproval = $threshold > 0 && $amount > $threshold;
            $status        = $needsApproval ? ExpenseStatus::Pending : ExpenseStatus::Approved;

            if ($needsApproval) {
                $check = $this->balanceChecker->checkDebit(
                    (int) $data['payment_account_id'],
                    $amount
                );

                if (! $check['allowed']) {
                    throw new \RuntimeException($check['message']);
                }

                // Store warning in session so Livewire can display it
                if (isset($check['warning'])) {
                    session()->flash('balance_warning', $check['warning']);
                }
            }

            $expense = Expense::create([
                'shop_id'             => $shop->id,
                'branch_id'           => $data['branch_id'],
                'expense_category_id' => $data['expense_category_id'],
                'payment_account_id'  => $data['payment_account_id'],
                'reference_number'    => $data['reference_number'] ?? null,
                'amount'              => $amount,
                'expense_date'        => $data['expense_date'],
                'description'         => $data['description'],
                'receipt_path'        => $data['receipt_path'] ?? null,
                'status'              => $status,
                'notes'               => $data['notes'] ?? null,
                'created_by'          => $actor->id,
                'approved_by'         => $needsApproval ? null : $actor->id,
                'approved_at'         => $needsApproval ? null : now(),
            ]);

            // Only post journal entry for immediately approved expenses
            if (! $needsApproval) {
                $this->postJournalEntry($shop, $expense, $data, $actor);
            }

            if ($needsApproval) {
                DB::afterCommit(fn () => event(new ExpensePendingApproval($expense, $shop)));
            }

            return $expense->fresh(['category', 'paymentAccount']);
        });
    }

    public function postJournalEntry(Shop $shop, Expense $expense, array $data, User $actor): void
    {
        $category = ExpenseCategory::withoutGlobalScopes()->findOrFail($expense->expense_category_id);

        $expenseGlCode  = $category->gl_account_code ?? '6030';
        $expenseAccount = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('code', $expenseGlCode)
            ->first()
            ?? Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '6030')->firstOrFail();

        $payAccount   = PaymentAccount::withoutGlobalScopes()->findOrFail($expense->payment_account_id);
        $payGlAccount = Account::withoutGlobalScopes()->findOrFail($payAccount->account_id);

        $this->accounting->postEntry(
            shop: $shop,
            description: "{$category->name}: {$expense->description}",
            lines: [
                ['account_id' => $expenseAccount->id, 'debit'  => (float) $expense->amount,
                 'description' => $expense->description],
                ['account_id' => $payGlAccount->id,   'credit' => (float) $expense->amount,
                 'description' => "Paid via {$payAccount->name}"],
            ],
            entryDate: $expense->expense_date->toDateTime(),
            reference: $expense,
            branchId: $expense->branch_id,
            actor: $actor,
        );
    }
}