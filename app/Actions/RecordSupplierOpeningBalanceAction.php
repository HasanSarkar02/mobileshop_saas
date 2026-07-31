<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\SupplierOpeningBalance;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecordSupplierOpeningBalanceAction
{
    public function __construct(
        private readonly AccountingService $accounting,
    ) {}

    /**
     * Records a supplier's pre-existing due (amount owed from before this
     * system was adopted) as a one-time opening balance.
     *
     * Journal: Dr Opening Balance Equity (3020) / Cr Accounts Payable -
     * Suppliers (2000) — mirrors CustomerLedgerService::recordOpeningBalance(),
     * reversed because a supplier balance is a liability, not a receivable.
     *
     * The DB-level unique constraint (shop_id, supplier_id) on
     * supplier_opening_balances is the real guarantee against duplicates —
     * this exists() check just gives a friendlier error message first.
     */
    public function execute(Shop $shop, Supplier $supplier, array $data, User $actor): SupplierOpeningBalance
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new RuntimeException('Opening balance amount must be greater than zero.');
        }

        if (SupplierOpeningBalance::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('supplier_id', $supplier->id)
            ->exists()
        ) {
            throw new RuntimeException(
                "An opening balance has already been recorded for {$supplier->name}. ".
                "It can't be entered twice — a wrong amount needs a manual accounting adjustment to correct."
            );
        }

        return DB::transaction(function () use ($shop, $supplier, $data, $actor, $amount) {
            $referenceNumber = $this->nextReferenceNumber($shop);

            $equityAccount = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('code', '3020') // Opening Balance Equity
                ->firstOrFail();

            $apAccount = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('code', '2000') // Accounts Payable - Suppliers
                ->firstOrFail();

            $journalEntry = $this->accounting->postEntry(
                shop:        $shop,
                description: "Opening balance — {$supplier->name} ({$referenceNumber})",
                lines: [
                    ['account_id' => $equityAccount->id, 'debit'  => $amount,
                     'description' => "Opening balance — {$supplier->name}"],
                    ['account_id' => $apAccount->id,      'credit' => $amount,
                     'description' => "Payable brought forward — {$supplier->name}"],
                ],
                entryDate: new \DateTime($data['balance_date']),
                reference: $supplier,
                actor:     $actor,
            );

            $openingBalance = SupplierOpeningBalance::create([
                'shop_id'          => $shop->id,
                'supplier_id'      => $supplier->id,
                'reference_number' => $referenceNumber,
                'amount'           => $amount,
                'balance_date'     => $data['balance_date'],
                'notes'            => $data['notes'] ?? null,
                'journal_entry_id' => $journalEntry->id,
                'created_by'       => $actor->id,
            ]);

            // Supplier owes more as a result — increment the same
            // denormalized total every other supplier-balance mutation uses.
            Supplier::withoutGlobalScopes()
                ->where('id', $supplier->id)
                ->increment('current_balance', $amount);

            return $openingBalance->fresh(['journalEntry']);
        });
    }

    private function nextReferenceNumber(Shop $shop): string
    {
        $year = now()->format('Y');

        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "sup_ob_{$year}"]
        );

        $sequence = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "sup_ob_{$year}")
            ->value('current_value');

        return sprintf('SOB-%s-%05d', $year, $sequence);
    }
}