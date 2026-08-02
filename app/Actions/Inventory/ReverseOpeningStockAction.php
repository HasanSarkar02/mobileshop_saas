<?php

namespace App\Actions\Inventory;

use App\Enums\UnitStatus;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\Shop;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\UnitStatusTransitioner;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReverseOpeningStockAction
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly UnitStatusTransitioner $transitioner,
    ) {}
    /**
     * Reverse an opening stock entry — non-serialized quantity or an entire
     * serialized (IMEI) batch. Never edits the original record; creates a
     * linked reversal row instead, exactly like Treasury does for money.
     *
     * @return array{reversal: StockAdjustment, warning: ?string}
     */
    public function execute(Shop $shop, StockAdjustment $original, string $reason, User $actor): array
    {
        if ($original->shop_id !== $shop->id) {
            throw new RuntimeException('This entry does not belong to your shop.');
        }

        if ($original->adjustment_type !== 'opening_stock') {
            throw new RuntimeException('Only opening stock entries can be reversed with this action.');
        }

        if ($original->isReversed()) {
            throw new RuntimeException('This entry has already been reversed.');
        }

        return DB::transaction(function () use ($shop, $original, $reason, $actor) {

            $journalEntry = null;
            if ($original->journal_entry_id) {
                $journalEntry = $this->accounting->reverseEntry(
                    $original->journalEntry()->withoutGlobalScopes()->firstOrFail(),
                    "Opening stock correction — {$reason}",
                    $actor,
                );
            }

            $hasUnits = $original->productUnits()->exists();
            $warning  = null;

            if ($hasUnits) {
                $this->reverseSerializedBatch($original);
            } else {
                $warning = $this->reverseNonSerializedQuantity($shop, $original);
            }

            $reversal = StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $original->branch_id,
                'product_variant_id' => $original->product_variant_id,
                'adjustment_type'    => 'opening_stock_reversal',
                'quantity'           => $original->quantity,
                'unit_cost'          => $original->unit_cost,
                'total_cost'         => $original->total_cost,
                'reason'             => $reason,
                'reversal_of_id'     => $original->id,
                'reversal_reason'    => $reason,
                'journal_entry_id'   => $journalEntry?->id,
                'created_by'         => $actor->id,
            ]);

            $original->update([
                'reversed_by_id' => $reversal->id,
                'reversed_at'    => now(),
            ]);

            return ['reversal' => $reversal, 'warning' => $warning];
        });
    }

    /**
     * Rolls the quantity back. Weighted-average cost cannot be un-mixed
     * retroactively — if other stock was added for this product afterward,
     * the average_cost left behind may need a manual look.
     */
    private function reverseNonSerializedQuantity(Shop $shop, StockAdjustment $original): ?string
    {
        $stock = BranchStock::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('branch_id', $original->branch_id)
            ->where('product_variant_id', $original->product_variant_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new RuntimeException('No branch stock record found for this product — it may already have been adjusted elsewhere.');
        }

        $newQty = (float) $stock->quantity - (float) $original->quantity;

        if ($newQty < 0) {
            throw new RuntimeException(
                "Cannot reverse — only {$stock->quantity} unit(s) remain in stock, but this entry added {$original->quantity}. " .
                'Some of this batch has already been sold or moved. This entry can no longer be cleanly reversed — ' .
                'use a stock adjustment instead, or contact support.'
            );
        }

        $stock->update(['quantity' => $newQty]);

        return $newQty > 0
            ? "Quantity corrected. Note: {$newQty} unit(s) of other stock for this product remain — please verify the average cost is still correct."
            : null;
    }

    private function reverseSerializedBatch(StockAdjustment $original): void
    {
        $unitIds = ProductUnit::withoutGlobalScopes()
            ->where('stock_adjustment_id', $original->id)
            ->pluck('id');

        if ($unitIds->isEmpty()) {
            throw new RuntimeException('No units are linked to this batch — nothing to reverse.');
        }

        $notInStock = ProductUnit::withoutGlobalScopes()
            ->whereIn('id', $unitIds)
            ->where(function ($q) {
                $q->where('status', '!=', UnitStatus::InStock->value)
                ->orWhere('is_archived', true);
            })
            ->first();

        if ($notInStock) {
            throw new RuntimeException(
                "Cannot reverse — IMEI {$notInStock->serial_number} is no longer in stock (status: {$notInStock->status->value}). " .
                'A batch can only be reversed as a whole if every unit in it is still unsold and in stock.'
            );
        }

        foreach ($unitIds as $unitId) {
            $this->transitioner->voidForReversal($unitId);
        }
    }
}