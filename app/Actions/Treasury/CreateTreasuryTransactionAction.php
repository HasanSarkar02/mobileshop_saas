<?php

namespace App\Actions\Treasury;

use App\Enums\TreasuryTransactionStatus;
use App\Enums\TreasuryTransactionType;
use App\Models\Shop;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Events\TreasuryPendingApproval;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateTreasuryTransactionAction
{
    public function __construct(
        private readonly ApproveTreasuryTransactionAction $approveAction,
    ) {}

    public function execute(Shop $shop, array $data, User $actor): TreasuryTransaction
    {
        return DB::transaction(function () use ($shop, $data, $actor) {
            $type   = TreasuryTransactionType::from($data['transaction_type']);
            $amount = (float) $data['amount'];
            $fee    = (float) ($data['fee_amount'] ?? 0);

            // ── Validate ───────────────────────────────────────────────────────
            if ($amount <= 0) {
                throw new RuntimeException('Amount must be greater than zero.');
            }

            if ($fee < 0) {
                throw new RuntimeException('Fee cannot be negative.');
            }

            if ($fee >= $amount && $fee > 0) {
                throw new RuntimeException('Fee cannot be equal to or greater than the gross amount.');
            }

            // Same account guard
            if (
                isset($data['from_payment_account_id'], $data['to_payment_account_id']) &&
                $data['from_payment_account_id'] === $data['to_payment_account_id']
            ) {
                throw new RuntimeException('Source and destination accounts cannot be the same.');
            }

            // Period lock check
            $transactionDate = $data['transaction_date'];
            if ($shop->books_locked_through && $transactionDate <= $shop->books_locked_through) {
                throw new RuntimeException(
                    "Transaction date {$transactionDate} falls within a locked accounting period " .
                    "(locked through: {$shop->books_locked_through})."
                );
            }

            // Duplicate opening balance guard
            if ($type === TreasuryTransactionType::OpeningBalance) {
                $existing = TreasuryTransaction::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('transaction_type', 'opening_balance')
                    ->where('to_payment_account_id', $data['to_payment_account_id'])
                    ->whereNotIn('status', ['rejected', 'reversed'])
                    ->exists();

                if ($existing) {
                    throw new RuntimeException(
                        'An opening balance entry already exists for this account. ' .
                        'Reverse the existing one before creating a new one.'
                    );
                }
            }

            // ── Determine approval ─────────────────────────────────────────────
            $needsApproval = $this->requiresApproval($type, $amount, $shop, $actor);

            if (! $needsApproval && $data['from_payment_account_id'] ?? null) {
                $checker = app(\App\Services\AccountBalanceChecker::class);
                $check   = $checker->checkDebit(
                    (int) $data['from_payment_account_id'],
                    $amount
                );

                if (! $check['allowed']) {
                    throw new RuntimeException($check['message']);
                }

                if (isset($check['warning'])) {
                    session()->flash('balance_warning', $check['warning']);
                }
            }

            // ── Create transaction record ──────────────────────────────────────
            $txnNumber = $this->nextNumber($shop);

            $txn = TreasuryTransaction::create([
                'shop_id'                   => $shop->id,
                'branch_id'                 => $data['branch_id'],
                'transaction_number'        => $txnNumber,
                'transaction_type'          => $type->value,
                'transaction_category'      => $type->category()->value,
                'status'                    => $needsApproval
                    ? TreasuryTransactionStatus::PendingApproval->value
                    : TreasuryTransactionStatus::Draft->value,
                'from_payment_account_id'   => $data['from_payment_account_id'] ?? null,
                'to_payment_account_id'     => $data['to_payment_account_id'] ?? null,
                'amount'                    => $amount,
                'fee_amount'                => $fee,
                'net_amount'                => $amount - $fee,
                'transaction_date'          => $transactionDate,
                'value_date'                => $data['value_date'] ?? null,
                'description'               => $data['description'],
                'reference_number'          => $data['reference_number'] ?? null,
                'third_party_name'          => $data['third_party_name'] ?? null,
                'third_party_reference'     => $data['third_party_reference'] ?? null,
                'approval_required'         => $needsApproval,
                'approval_threshold_snapshot' => $needsApproval
                    ? ($shop->treasury_approval_threshold ?? 0)
                    : null,
                'attachments'               => $data['attachments'] ?? null,
                'notes'                     => $data['notes'] ?? null,
                'created_by'                => $actor->id,
                'updated_by'                => $actor->id,
            ]);

            // activity()
            //     ->causedBy($actor)
            //     ->performedOn($txn)
            //     ->withProperties(['type' => $type->value, 'amount' => $amount, 'needs_approval' => $needsApproval])
            //     ->log('treasury_transaction.created');

            // ── Auto-approve if no approval needed ─────────────────────────────
            if (! $needsApproval) {
                $txn = $this->approveAction->execute($txn, $actor);
            }

            if ($needsApproval) {
                DB::afterCommit(fn () => event(new TreasuryPendingApproval($txn, $shop)));
            }

            return $txn->fresh(['fromAccount', 'toAccount', 'journalEntry']);
        });
    }

    private function requiresApproval(
        TreasuryTransactionType $type,
        float                   $amount,
        Shop                    $shop,
        User                    $actor,
    ): bool {
        // Equity + loan types always require Owner approval
        if ($type->alwaysRequiresApproval()) {
            return true;
        }

        // Cash adjustments by non-Owner always require approval
        if (
            in_array($type, [
                TreasuryTransactionType::CashOver,
                TreasuryTransactionType::CashShort,
            ]) && ! $actor->isOwner()
        ) {
            return true;
        }

        // Amount threshold
        $threshold = (float) ($shop->treasury_approval_threshold ?? 0);
        if ($threshold > 0 && $amount > $threshold) {
            return true;
        }

        return false;
    }

    private function nextNumber(Shop $shop): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "treasury_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "treasury_{$year}")
            ->value('current_value');
        return sprintf('TRX-%s-%05d', $year, $seq);
    }
}