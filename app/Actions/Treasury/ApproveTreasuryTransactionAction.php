<?php

namespace App\Actions\Treasury;

use App\Enums\TreasuryTransactionStatus;
use App\Models\Shop;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\Treasury\TreasuryJournalBuilder;
use Illuminate\Support\Facades\DB;
use App\Events\TreasuryApproved;
use RuntimeException;

class ApproveTreasuryTransactionAction
{
    public function __construct(
        private readonly TreasuryJournalBuilder $builder,
        private readonly AccountingService      $accounting,
    ) {}

    public function execute(TreasuryTransaction $txn, User $actor): TreasuryTransaction
    {
        return DB::transaction(function () use ($txn, $actor) {
            if (! in_array($txn->status, [
                TreasuryTransactionStatus::Draft,
                TreasuryTransactionStatus::PendingApproval,
            ])) {
                throw new RuntimeException(
                    "Cannot approve a transaction with status: {$txn->status->label()}"
                );
            }

            // Period lock re-check at approval time
            $shop = $txn->shop()->withoutGlobalScopes()->findOrFail($txn->shop_id);

            if (
                $shop->books_locked_through &&
                $txn->transaction_date->toDateString() <= $shop->books_locked_through
            ) {
                throw new RuntimeException(
                    "Transaction date falls within a locked period. Cannot approve."
                );
            }

            // Ensure all required GL accounts exist
            $this->builder->validateGlAccounts($shop->id);

            // Build journal lines
            $lines = $this->builder->build($txn);

            // Post journal entry via existing AccountingService
            $journalEntry = $this->accounting->postEntry(
                shop:        $shop,
                description: "{$txn->transaction_type->label()} — {$txn->description}",
                lines:       $lines,
                entryDate:   $txn->transaction_date->toDateTime(),
                reference:   $txn,
                branchId:    $txn->branch_id,
                actor:       $actor,
            );

            // Mark as completed
            $txn->update([
                'status'            => TreasuryTransactionStatus::Completed->value,
                'journal_entry_id'  => $journalEntry->id,
                'approved_by'       => $actor->id,
                'approved_at'       => now(),
                'updated_by'        => $actor->id,
            ]);

            // activity()
            //     ->causedBy($actor)
            //     ->performedOn($txn)
            //     ->withProperties(['journal_entry_id' => $journalEntry->id])
            //     ->log('treasury_transaction.completed');
            DB::afterCommit(fn () => event(new TreasuryApproved($txn, $shop, $actor)));
            return $txn->fresh(['fromAccount', 'toAccount', 'journalEntry', 'approvedBy']);
        });
    }
}