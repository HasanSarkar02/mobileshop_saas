<?php

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveExpenseAction
{
    public function __construct(
        private readonly RecordExpenseAction $recorder,
        private readonly \App\Services\AccountBalanceChecker $balanceChecker,
        ) {}

    public function approve(Expense $expense, User $actor): Expense
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            throw new RuntimeException("Only pending expenses can be approved.");
        }

        return DB::transaction(function () use ($expense, $actor) {

         $check = $this->balanceChecker->checkDebit(
                $expense->payment_account_id,
                (float) $expense->amount
            );

            if (! $check['allowed']) {
                throw new RuntimeException(
                    "Cannot approve: {$check['message']}"
                );
            }
            $expense->update([
                'status'      => ExpenseStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            // Now post the journal entry (deferred from entry time)
            $shop = $expense->shop()->withoutGlobalScopes()->findOrFail($expense->shop_id);
            $this->recorder->postJournalEntry($shop, $expense, [], $actor);

            return $expense->fresh();
        });
    }

    public function reject(Expense $expense, string $reason, User $actor): Expense
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            throw new RuntimeException("Only pending expenses can be rejected.");
        }

        $expense->update([
            'status'           => ExpenseStatus::Rejected,
            'rejected_by'      => $actor->id,
            'rejection_reason' => $reason,
            'rejected_at'      => now(),
        ]);

        return $expense->fresh();
    }
}