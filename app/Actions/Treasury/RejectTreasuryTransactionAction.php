<?php

namespace App\Actions\Treasury;

use App\Enums\TreasuryTransactionStatus;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Events\TreasuryRejected;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RejectTreasuryTransactionAction
{
    public function execute(TreasuryTransaction $txn, string $reason, User $actor): TreasuryTransaction
    {
        if ($txn->status !== TreasuryTransactionStatus::PendingApproval) {
            throw new RuntimeException('Only pending transactions can be rejected.');
        }

        if (strlen(trim($reason)) < 5) {
            throw new RuntimeException('Please provide a meaningful rejection reason.');
        }

        $txn->update([
            'status'           => TreasuryTransactionStatus::Rejected->value,
            'rejected_by'      => $actor->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
            'updated_by'       => $actor->id,
        ]);

        activity()
            ->causedBy($actor)
            ->performedOn($txn)
            ->withProperties(['reason' => $reason])
            ->log('treasury_transaction.rejected');
        $shop = Shop::withoutGlobalScopes()->findOrFail($txn->shop_id);
        DB::afterCommit(fn () => event(new TreasuryRejected($txn, $shop, $actor, $reason)));
        return $txn->fresh();
    }
}