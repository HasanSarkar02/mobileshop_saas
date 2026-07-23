<?php

namespace App\Actions;

use App\Enums\CustomerTransactionType;
use App\Enums\FPReceivableStatus;
use App\Enums\SaleStatus;
use App\Enums\UnitStatus;
use App\Models\BranchStock;
use App\Models\CustomerTransaction;
use App\Models\FinancePartnerReceivable;
use App\Models\JournalEntry;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Events\SaleVoided;
use App\Services\UnitStatusTransitioner;

class VoidSaleAction
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly UnitStatusTransitioner $transitioner,
    ) {}

    public function execute(Sale $sale, string $reason, User $actor): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $actor) {
            if ($sale->status !== SaleStatus::Confirmed) {
                throw new RuntimeException('Only confirmed sales can be voided.');
            }

            $shop = $sale->shop()->withoutGlobalScopes()->findOrFail($sale->shop_id);

            if ($shop->books_locked_through && $sale->confirmed_at?->lte($shop->books_locked_through)) {
                throw new RuntimeException('Cannot void a sale in a locked accounting period.');
            }

            // ── 1. Reverse GL entry ────────────────────────────────────────────
            $originalEntry = JournalEntry::withoutGlobalScopes()
                ->where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->first();

            if ($originalEntry) {
                $this->accounting->reverseEntry(
                    $originalEntry,
                    "Void of sale {$sale->sale_number}: {$reason}",
                    $actor,
                );
            }

            // ── 2. Restore inventory ───────────────────────────────────────────
            foreach ($sale->items as $item) {
                if ($item->product_unit_id) {

                    $this->transitioner->reverseVoidedSale($item->product_unit_id, $sale);
                    // Force direct update to bypass any model casting issues
                    // ProductUnit::withoutGlobalScopes()
                    //     ->where('id', $item->product_unit_id)
                    //     ->update([
                    //         'status'           => 'in_stock',   // direct string value
                    //         'sold_at'          => null,
                    //         'is_archived'      => false,
                    //         'disposition_type' => null,
                    //         'disposition_id'   => null,
                    //     ]);
                } else {

                    DB::table('branch_stocks')
                        ->where('shop_id', $sale->shop_id)
                        ->where('branch_id', $sale->branch_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->lockForUpdate()
                        ->exists();

                    $affected = BranchStock::withoutGlobalScopes()
                        ->where('shop_id', $sale->shop_id)
                        ->where('branch_id', $sale->branch_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->increment('quantity', $item->quantity);

                    if ($affected === 0) {
                        // Row genuinely doesn't exist yet for this branch —
                        // upsert to create it atomically rather than a
                        // separate check-then-create that could race with
                        // another void or a concurrent purchase receipt
                        // creating the same row.
                        DB::table('branch_stocks')->upsert(
                            [[
                                'shop_id'            => $sale->shop_id,
                                'branch_id'          => $sale->branch_id,
                                'product_variant_id' => $item->product_variant_id,
                                'quantity'           => $item->quantity,
                                'average_cost'       => $item->cost_price,
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ]],
                            ['branch_id', 'product_variant_id'],
                            ['quantity' => DB::raw('quantity + ' . (int) $item->quantity)]
                        );
                    }
                    // Non-serialized — restore at the ORIGINAL sale's branch
                    // $existing = BranchStock::withoutGlobalScopes()
                    //     ->where('shop_id', $sale->shop_id)
                    //     ->where('branch_id', $sale->branch_id)
                    //     ->where('product_variant_id', $item->product_variant_id)
                    //     ->first();

                    // if ($existing) {
                    //     $existing->increment('quantity', $item->quantity);
                    // } else {
                    //     // Create stock row if it doesn't exist (edge case)
                    //     BranchStock::create([
                    //         'shop_id'            => $sale->shop_id,
                    //         'branch_id'          => $sale->branch_id,
                    //         'product_variant_id' => $item->product_variant_id,
                    //         'quantity'           => $item->quantity,
                    //         'average_cost'       => $item->cost_price,
                    //     ]);
                    // }
                }
            }
            // ── 3. Reverse customer baki ───────────────────────────────────────
            $bakiPayments = $sale->payments->where('payment_type', 'customer_credit');

            if ($bakiPayments->isNotEmpty()) {
                $customer    = $sale->customer()->withoutGlobalScopes()->lockForUpdate()->findOrFail($sale->customer_id);
                $totalReversal = $bakiPayments->sum('amount');
                $newBalance  = max(0, (float) $customer->current_balance - $totalReversal);

                CustomerTransaction::create([
                    'shop_id'          => $sale->shop_id,
                    'customer_id'      => $sale->customer_id,
                    'transaction_type' => CustomerTransactionType::Adjustment->value,
                    'amount'           => $totalReversal,
                    'direction'        => 'credit',
                    'running_balance'  => $newBalance,
                    'reference_type'   => Sale::class,
                    'reference_id'     => $sale->id,
                    'notes'            => "Reversal — void of sale {$sale->sale_number}",
                    'created_by'       => $actor->id,
                ]);

                $customer->update([
                    'current_balance'       => $newBalance,
                    'total_purchase_amount' => max(0, (float) $customer->total_purchase_amount - $totalReversal),
                ]);
            }

            // ── 4. Cancel finance partner receivables ──────────────────────────
            FinancePartnerReceivable::where('sale_id', $sale->id)
                ->whereIn('status', [FPReceivableStatus::Pending->value, FPReceivableStatus::Partial->value])
                ->update(['status' => FPReceivableStatus::Cancelled->value]);

            // ── 5. Mark sale as voided ─────────────────────────────────────────
            $sale->update([
                'status'      => SaleStatus::Voided,
                'voided_by'   => $actor->id,
                'void_reason' => $reason,
                'voided_at'   => now(),
            ]);

            DB::afterCommit(fn () => event(new SaleVoided($sale, $shop, $actor, $reason)));

            return $sale->fresh();
        });
    }
}