<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\UnitStatusTransitioner;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessPurchaseReturnAction
{
    public function __construct(
        private readonly AccountingService      $accounting,
        private readonly UnitStatusTransitioner $transitioner,
    ) {}

    public function execute(Purchase $purchase, array $data, User $actor): PurchaseReturn
    {
        if ($purchase->payment_status === 'paid') {
            throw new RuntimeException(
                'This purchase is fully paid. A purchase return against a paid invoice ' .
                'requires a cash refund from the supplier. Set settlement_type = cash_refund.'
            );
        }

        return DB::transaction(function () use ($purchase, $data, $actor) {
            $shop     = Shop::withoutGlobalScopes()->findOrFail($purchase->shop_id);
            $supplier = $purchase->supplier()->withoutGlobalScopes()->lockForUpdate()->findOrFail($purchase->supplier_id);

            $returnNumber = $this->nextReturnNumber($shop);
            $totalAmount  = 0.0;

            // ── Create return record ────────────────────────────────────────────
            $return = PurchaseReturn::create([
                'shop_id'          => $shop->id,
                'branch_id'        => $purchase->branch_id,
                'purchase_id'      => $purchase->id,
                'supplier_id'      => $supplier->id,
                'return_number'    => $returnNumber,
                'total_amount'     => 0, // will update below
                'return_date'      => $data['return_date'],
                'return_reason'    => $data['return_reason'],
                'notes'            => $data['notes'] ?? null,
                'settlement_type'  => $data['settlement_type'],
                'refund_account_id'=> $data['refund_account_id'] ?? null,
                'created_by'       => $actor->id,
            ]);

            // ── Process each return item ────────────────────────────────────────
            foreach ($data['items'] as $item) {
                $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                $totalAmount += $lineTotal;

                PurchaseReturnItem::create([
                    'purchase_return_id'    => $return->id,
                    'purchase_line_item_id' => $item['purchase_line_item_id'],
                    'product_variant_id'    => $item['product_variant_id'],
                    'product_unit_id'       => $item['product_unit_id'] ?? null,
                    'quantity'              => $item['quantity'],
                    'unit_cost'             => $item['unit_cost'],
                    'line_total'            => $lineTotal,
                    'condition'             => $item['condition'] ?? 'good',
                    'notes'                 => $item['notes'] ?? null,
                ]);

                // Remove serialized unit from inventory
                if (! empty($item['product_unit_id'])) {
                    $unit = ProductUnit::withoutGlobalScopes()->findOrFail($item['product_unit_id']);
                    $this->transitioner->transition(
                        $unit,
                        \App\Enums\UnitStatus::ReturnedPendingInspection,
                        $return
                    );
                    // Mark as returned to supplier
                    $unit->update(['is_archived' => true]);
                } else {
                    // Non-serialized — reduce branch stock
                    BranchStock::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)
                        ->where('branch_id', $purchase->branch_id)
                        ->where('product_variant_id', $item['product_variant_id'])
                        ->decrement('quantity', $item['quantity']);
                }
            }

            $return->update(['total_amount' => $totalAmount]);

            // ── Journal entry based on settlement type ──────────────────────────
            $inventoryAcc = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();
            $apAccount    = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '2000')->firstOrFail();
            $purchaseReturnAcc = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '5010')->firstOrFail();

            if ($data['settlement_type'] === 'cash_refund') {
                // Supplier pays us back in cash
                // Dr Cash/Bank, Cr Inventory (we returned the goods, so inventory reduces)
                // Actually: Dr Cash / Cr Purchase Returns & Allowances
                // AND: Dr Purchase Returns / Cr Inventory
                $pa    = \App\Models\PaymentAccount::withoutGlobalScopes()->findOrFail($data['refund_account_id']);
                $payGl = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

                $journalEntry = $this->accounting->postEntry(
                    shop:        $shop,
                    description: "Purchase return (cash refund) — {$returnNumber}",
                    lines: [
                        ['account_id' => $payGl->id,           'debit'  => $totalAmount, 'description' => 'Cash refund from supplier'],
                        ['account_id' => $purchaseReturnAcc->id,'credit' => $totalAmount, 'description' => "Return to {$supplier->name}"],
                        ['account_id' => $purchaseReturnAcc->id,'debit'  => $totalAmount, 'description' => 'COGS reversal — returned goods'],
                        ['account_id' => $inventoryAcc->id,     'credit' => $totalAmount, 'description' => 'Inventory reduced — returned to supplier'],
                    ],
                    entryDate: new \DateTime($data['return_date']),
                    reference: $return,
                    branchId:  $purchase->branch_id,
                    actor:     $actor,
                );
            } else {
                // Credit note — reduces what we owe supplier (AP decreases)
                // Dr Accounts Payable / Cr Purchase Returns & Allowances
                // AND: Dr Purchase Returns / Cr Inventory
                $journalEntry = $this->accounting->postEntry(
                    shop:        $shop,
                    description: "Purchase return (credit note) — {$returnNumber}",
                    lines: [
                        ['account_id' => $apAccount->id,        'debit'  => $totalAmount, 'description' => "AP reduced — {$supplier->name} credit note"],
                        ['account_id' => $purchaseReturnAcc->id,'credit' => $totalAmount, 'description' => "Purchase return {$returnNumber}"],
                        ['account_id' => $purchaseReturnAcc->id,'debit'  => $totalAmount, 'description' => 'COGS reversal — returned goods'],
                        ['account_id' => $inventoryAcc->id,     'credit' => $totalAmount, 'description' => 'Inventory reduced — returned to supplier'],
                    ],
                    entryDate: new \DateTime($data['return_date']),
                    reference: $return,
                    branchId:  $purchase->branch_id,
                    actor:     $actor,
                );

                // Reduce supplier outstanding balance
                $newBalance = max(0, (float) $supplier->current_balance - $totalAmount);
                $supplier->update(['current_balance' => $newBalance]);
            }

            $return->update(['journal_entry_id' => $journalEntry->id]);

            return $return->fresh(['items.variant', 'supplier', 'purchase']);
        });
    }

    private function nextReturnNumber(Shop $shop): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "pur_return_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "pur_return_{$year}")
            ->value('current_value');
        return sprintf('PRN-%s-%05d', $year, $seq);
    }
}