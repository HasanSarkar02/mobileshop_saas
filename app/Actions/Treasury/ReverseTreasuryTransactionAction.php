<?php

namespace App\Actions\Treasury;

use App\Enums\TreasuryTransactionStatus;
use App\Events\TreasuryReversed;
use App\Models\Shop;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReverseTreasuryTransactionAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(TreasuryTransaction $txn, string $reason, User $actor): TreasuryTransaction
    {
        if (! $txn->isReversible()) {
            throw new RuntimeException(
                'This transaction cannot be reversed. ' .
                'Only completed transactions with no existing reversal can be reversed.'
            );
        }

        if (strlen(trim($reason)) < 5) {
            throw new RuntimeException('Please provide a meaningful reversal reason.');
        }

        $shop = $txn->shop()->withoutGlobalScopes()->findOrFail($txn->shop_id);

        // Reversal posts TODAY — not back-dated to original transaction
        // This ensures period-lock compatibility
        $reversalDate = now()->toDateString();

        if (
            $shop->books_locked_through &&
            $reversalDate <= $shop->books_locked_through
        ) {
            throw new RuntimeException(
                "Today's date falls within a locked accounting period. Cannot post reversal."
            );
        }

        return DB::transaction(function () use ($txn, $reason, $actor, $shop) {
            // Reverse the original journal entry using existing AccountingService
            $originalEntry = $txn->journalEntry;
            if (! $originalEntry) {
                throw new RuntimeException('Original journal entry not found. Cannot reverse.');
            }

            $reversalEntry = $this->accounting->reverseEntry(
                $originalEntry,
                "Reversal of {$txn->transaction_number}: {$reason}",
                $actor,
            );

            // Create reversal treasury transaction record (the audit trail document)
            $reversalTxn = TreasuryTransaction::create([
                'shop_id'                  => $txn->shop_id,
                'branch_id'                => $txn->branch_id,
                'transaction_number'       => $this->nextReversalNumber($txn->transaction_number),
                'transaction_type'         => $txn->transaction_type->value,
                'transaction_category'     => $txn->transaction_category->value,
                'status'                   => TreasuryTransactionStatus::Reversed->value,
                'from_payment_account_id'  => $txn->to_payment_account_id,  // reversed
                'to_payment_account_id'    => $txn->from_payment_account_id, // reversed
                'amount'                   => $txn->amount,
                'fee_amount'               => $txn->fee_amount,
                'net_amount'               => $txn->net_amount,
                'transaction_date'         => now()->toDateString(),
                'description'              => "REVERSAL: {$txn->description}",
                'reference_number'         => "REV-{$txn->transaction_number}",
                'journal_entry_id'         => $reversalEntry->id,
                'reversal_of_id'           => $txn->id,
                'reversal_reason'          => $reason,
                'approved_by'              => $actor->id,
                'approved_at'              => now(),
                'created_by'               => $actor->id,
                'updated_by'               => $actor->id,
            ]);

            // Mark original as reversed
            $txn->update([
                'status'        => TreasuryTransactionStatus::Reversed->value,
                'reversed_by_id'=> $reversalTxn->id,
                'reversed_at'   => now(),
                'reversal_reason' => $reason,
                'updated_by'    => $actor->id,
            ]);

            // activity()
            //     ->causedBy($actor)
            //     ->performedOn($txn)
            //     ->withProperties([
            //         'reason'              => $reason,
            //         'reversal_txn_id'     => $reversalTxn->id,
            //         'reversal_journal_id' => $reversalEntry->id,
            //     ])
            //     ->log('treasury_transaction.reversed');
            DB::afterCommit(fn () => event(new TreasuryReversed($txn, $reversalTxn, $shop, $actor)));
            return $reversalTxn->fresh();
        });
    }

    private function nextReversalNumber(string $originalNumber): string
    {
        return 'REV-' . $originalNumber;
    }
}