<?php

namespace App\Actions;

use App\Events\PurchaseReturnProcessed;
use App\Models\Account;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseLineItem;
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
        // Validate settlement type FIRST — never trust the caller (Livewire validates too,
        // but server-side is authoritative). Only the two implemented types are accepted.
        $settlement = $data['settlement_type'] ?? null;
        if (! in_array($settlement, ['credit_note', 'cash_refund'], true)) {
            throw new RuntimeException(
                "Invalid settlement type. Must be 'credit_note' or 'cash_refund'."
            );
        }

        // A fully-paid invoice has zero outstanding AP, so a credit note would be
        // meaningless (and the old max(0, ...) clamp would silently destroy value).
        // Cash refunds are still allowed — money physically comes back.
        if ($purchase->payment_status === 'paid' && $settlement !== 'cash_refund') {
            throw new RuntimeException(
                'This purchase is fully paid. A purchase return against a paid invoice ' .
                'requires a cash refund from the supplier. Set settlement_type = cash_refund.'
            );
        }

        if ($settlement === 'cash_refund' && empty($data['refund_account_id'])) {
            throw new RuntimeException('Cash refund requires a refund account.');
        }

        // Credit-note oversize guard (before the transaction so nothing is written).
        // NOTE: amounts are DECIMAL(14,2) taka, not integer paisa — compared here in
        // integer paisa via round(x * 100) for exact 2-decimal comparison.
        if ($settlement === 'credit_note') {
            $requestedTotal = 0.0;
            foreach ($data['items'] ?? [] as $item) {
                $requestedTotal += (float) $item['unit_cost'] * (int) $item['quantity'];
            }

            $outstanding = $purchase->effectiveTotalAmount() - (float) $purchase->amount_paid;

            if ((int) round($requestedTotal * 100) > (int) round($outstanding * 100)) {
                throw new RuntimeException(
                    'Credit note (৳' . number_format($requestedTotal, 2) . ') exceeds outstanding payable ' .
                    '(৳' . number_format($outstanding, 2) . ') — use cash refund for the excess.'
                );
            }
        }

        return DB::transaction(function () use ($purchase, $data, $actor) {
            // Quantity / price / total caps BEFORE anything is written. Runs inside
            // the transaction on the locked purchase row so two concurrent
            // returns serialize here — the loser sees the winner's quantities
            // and fails cleanly instead of double-spending stock/cash.
            $this->validateReturnableItems($purchase, $data['items'] ?? []);

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
                        \App\Enums\UnitStatus::RmaToSupplier,
                        $return
                    );

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

            $this->recalculatePurchaseStatus($purchase);

            DB::afterCommit(fn () => event(new PurchaseReturnProcessed($return, $shop)));
            return $return->fresh(['items.variant', 'supplier', 'purchase']);
        });
    }

    /**
     * Close the repeat-return loophole: every submitted item is checked against
     * what this purchase can still return. Without this, the same (bulk,
     * non-serialized) line could be returned over and over — each pass posting
     * another cash-refund journal and decrementing stock without bound.
     *
     * Enforces, in integer paisa where money is compared:
     *  - each item references a real line of THIS purchase, qty >= 1;
     *  - return price == purchase line price (the UI has no price field —
     *    anything else is tampered input and would print cash / erase AP);
     *  - serialized items: qty == 1, unit belongs to the line, still in_stock,
     *    never returned before (in this or any earlier return);
     *  - per-line cumulative returned qty (all settlements) <= purchased qty;
     *  - global cumulative returned total + this request <= purchase total.
     *
     * Must run inside the transaction on the locked row (see caller).
     */
    private function validateReturnableItems(Purchase $purchase, array $items): void
    {
        if (empty($items)) {
            throw new RuntimeException('No return items supplied.');
        }

        $locked = Purchase::withoutGlobalScopes()->lockForUpdate()->findOrFail($purchase->id);

        $lines = PurchaseLineItem::withoutGlobalScopes()
            ->with('variant.product')
            ->where('purchase_id', $locked->id)
            ->get()
            ->keyBy('id');

        // Cumulative quantities already returned per line (ALL settlements —
        // a cash-refunded unit is gone just as much as a credited one).
        $priorQty = DB::table('purchase_return_items as pri')
            ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
            ->where('pr.purchase_id', $locked->id)
            ->groupBy('pri.purchase_line_item_id')
            ->selectRaw('pri.purchase_line_item_id as line_id, SUM(pri.quantity) as qty')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->line_id => (int) $r->qty]);

        // Every serialized unit ever returned against this purchase.
        $returnedUnitIds = DB::table('purchase_return_items as pri')
            ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
            ->where('pr.purchase_id', $locked->id)
            ->whereNotNull('pri.product_unit_id')
            ->pluck('pri.product_unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $requestedTotal   = 0.0;
        $requestedPerLine = [];
        $seenUnits        = [];

        foreach (array_values($items) as $index => $item) {
            $label = 'Return item #' . ($index + 1);

            $line = $lines->get((int) ($item['purchase_line_item_id'] ?? 0));
            if (! $line) {
                throw new RuntimeException("{$label} does not belong to this purchase.");
            }

            if ((int) ($line->product_variant_id) !== (int) ($item['product_variant_id'] ?? 0)) {
                throw new RuntimeException("{$label} does not match the purchase line item.");
            }

            $qty = (int) ($item['quantity'] ?? 0);
            if ($qty < 1) {
                throw new RuntimeException("{$label}: quantity must be at least 1.");
            }

            if ((int) round((float) ($item['unit_cost'] ?? 0) * 100)
                !== (int) round((float) $line->unit_cost * 100)) {
                throw new RuntimeException(
                    "{$label}: return price must match the purchase price " .
                    '(৳' . number_format((float) $line->unit_cost, 2) . ').'
                );
            }

            $unitId = $item['product_unit_id'] ?? null;
            if (! empty($unitId)) {
                $unitId = (int) $unitId;
                if ($qty !== 1) {
                    throw new RuntimeException("{$label}: serialized (IMEI) returns must be one unit at a time.");
                }
                if (in_array($unitId, $seenUnits, true)) {
                    throw new RuntimeException("{$label}: the same unit is listed twice in this return.");
                }
                $seenUnits[] = $unitId;
                if (in_array($unitId, $returnedUnitIds, true)) {
                    throw new RuntimeException("{$label}: this unit was already returned and cannot be returned again.");
                }
                $unit = ProductUnit::withoutGlobalScopes()->find($unitId);
                if (! $unit
                    || (int) $unit->purchase_line_item_id !== (int) $line->id
                    || $unit->status !== \App\Enums\UnitStatus::InStock) {
                    throw new RuntimeException("{$label}: the selected unit is no longer available for return.");
                }
            }

            $requestedPerLine[$line->id] = ($requestedPerLine[$line->id] ?? 0) + $qty;
            $requestedTotal += (float) $item['unit_cost'] * $qty;
        }

        // Per-line caps.
        foreach ($requestedPerLine as $lineId => $qty) {
            $line      = $lines->get($lineId);
            $already   = (int) ($priorQty[$lineId] ?? 0);
            $remaining = (int) $line->quantity - $already;
            if ($qty > $remaining) {
                $name = $line->variant?->product?->name ?? ('line #' . $line->id);
                throw new RuntimeException(
                    "Only " . max(0, $remaining) . " of {$line->quantity} ({$name}) can still be returned " .
                    "— {$already} already returned. You asked for {$qty}."
                );
            }
        }

        // Global cap: everything returned so far + this request <= purchase total.
        $priorTotal = (float) PurchaseReturn::withoutGlobalScopes()
            ->where('purchase_id', $locked->id)
            ->sum('total_amount');
        if ((int) round(($priorTotal + $requestedTotal) * 100)
            > (int) round((float) $locked->total_amount * 100)) {
            throw new RuntimeException(
                'Total returned (৳' . number_format($priorTotal + $requestedTotal, 2) . ') would exceed the ' .
                'purchase total (৳' . number_format((float) $locked->total_amount, 2) . ').'
            );
        }
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

    /**
     * After a return, recalculate the purchase's effective outstanding
     * and update payment_status accordingly.
     *
     * Logic:
     *   effective_total = original_total - credit_note_returns
     *   outstanding     = effective_total - amount_paid
     *
     * If outstanding <= 0 → paid
     * If outstanding < effective_total → partial
     * else → unpaid
     */
    private function recalculatePurchaseStatus(Purchase $purchase): void
    {
        $purchase->refresh();

        // Sum of all credit-note returns on this purchase
        $totalCreditReturns = \App\Models\PurchaseReturn::withoutGlobalScopes()
            ->where('purchase_id', $purchase->id)
            ->where('settlement_type', 'credit_note')
            ->sum('total_amount');

        $effectiveTotal = max(0, (float) $purchase->total_amount - (float) $totalCreditReturns);
        $amountPaid     = (float) $purchase->amount_paid;
        $outstanding    = $effectiveTotal - $amountPaid;

        $newStatus = match (true) {
            $outstanding <= 0.005              => 'paid',
            $amountPaid > 0.005                => 'partial',
            default                            => 'unpaid',
        };

        // Only update if status actually changed to avoid unnecessary writes
        if ($purchase->payment_status !== $newStatus) {
            $purchase->update(['payment_status' => $newStatus]);
        }
    }
}