<?php

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\PaymentAccount;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoidExpenseAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(Expense $expense, string $reason, User $actor): Expense
    {
        if (! in_array($expense->status, [ExpenseStatus::Approved, ExpenseStatus::Pending])) {
            throw new RuntimeException("Only approved or pending expenses can be voided.");
        }

        return DB::transaction(function () use ($expense, $reason, $actor) {
            $shop = $expense->shop()->withoutGlobalScopes()->findOrFail($expense->shop_id);

            // Only reverse journal if it was approved (journal was posted)
            if ($expense->status === ExpenseStatus::Approved) {
                if ($shop->books_locked_through
                    && $expense->expense_date <= $shop->books_locked_through) {
                    throw new RuntimeException(
                        "Cannot void — expense date is in a locked accounting period."
                    );
                }

                $originalEntry = JournalEntry::withoutGlobalScopes()
                    ->where('reference_type', Expense::class)
                    ->where('reference_id', $expense->id)
                    ->first();

                if ($originalEntry) {
                    $this->accounting->reverseEntry(
                        $originalEntry,
                        "Void of expense #{$expense->id}: {$reason}",
                        $actor,
                    );
                }
            }

            $expense->update([
                'status'      => ExpenseStatus::Voided,
                'voided_by'   => $actor->id,
                'void_reason' => $reason,
                'voided_at'   => now(),
            ]);

            return $expense->fresh();
        });
    }
}