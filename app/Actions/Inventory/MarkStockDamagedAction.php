<?php
namespace App\Actions\Inventory;

use App\Models\Account;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class MarkStockDamagedAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    /**
     * Mark non-serialized stock as damaged.
     * Removes from available quantity, posts journal.
     */
    public function executeNonSerialized(
        Shop           $shop,
        ProductVariant $variant,
        Branch         $branch,
        float          $quantity,
        string         $reason,
        User           $actor,
    ): StockAdjustment {
        if ($quantity <= 0) {
            throw new \RuntimeException('Damaged quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($shop, $variant, $branch, $quantity, $reason, $actor) {
            $stock = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('branch_id', $branch->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->available_quantity < $quantity) {
                throw new \RuntimeException(
                    "Insufficient available stock. Available: " . ($stock?->available_quantity ?? 0)
                );
            }

            $unitCost   = (float) $stock->average_cost;
            $totalCost  = $unitCost * $quantity;

            // Reduce quantity + track damaged separately
            $stock->decrement('quantity', $quantity);
            $stock->increment('damaged_quantity', $quantity);

            // GL: Dr Inventory Shrinkage (6040) / Cr Inventory (1200)
            $journal = $this->postShrinkageJournal($shop, $branch, $totalCost, $variant->name, $actor);

            $adjustment = StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $branch->id,
                'product_variant_id' => $variant->id,
                'adjustment_type'    => 'damaged',
                'quantity'           => $quantity,
                'unit_cost'          => $unitCost,
                'total_cost'         => $totalCost,
                'reason'             => $reason,
                'journal_entry_id'   => $journal->id,
                'created_by'         => $actor->id,
            ]);

            return $adjustment;
        });
    }

    /**
     * Mark a serialized unit (IMEI) as damaged.
     */
    public function executeSerialized(
        Shop        $shop,
        ProductUnit $unit,
        string      $reason,
        User        $actor,
    ): StockAdjustment {
        if ($unit->status !== \App\Enums\UnitStatus::InStock) {
            throw new \RuntimeException("Unit is not in stock. Current status: {$unit->status->label()}");
        }

        return DB::transaction(function () use ($shop, $unit, $reason, $actor) {
            $unit->update(['status' => \App\Enums\UnitStatus::Damaged]);

            $totalCost = (float) $unit->cost_price;

            $journal = $this->postShrinkageJournal(
                $shop,
                $unit->branch,
                $totalCost,
                "IMEI: {$unit->serial_number}",
                $actor
            );

            return StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $unit->branch_id,
                'product_variant_id' => $unit->product_variant_id,
                'product_unit_id'    => $unit->id,
                'adjustment_type'    => 'damaged',
                'quantity'           => 1,
                'unit_cost'          => $totalCost,
                'total_cost'         => $totalCost,
                'reason'             => $reason,
                'journal_entry_id'   => $journal->id,
                'created_by'         => $actor->id,
            ]);
        });
    }

    private function postShrinkageJournal(
        Shop   $shop,
        Branch|\App\Models\Branch|null $branch,
        float  $cost,
        string $description,
        User   $actor,
    ): \App\Models\JournalEntry {
        $shrinkageGl = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('code', '6040')
            ->firstOrFail();

        $inventoryGl = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('code', '1200')
            ->firstOrFail();

        return $this->accounting->postEntry(
            shop:        $shop,
            description: "Stock damaged — {$description}",
            lines: [
                ['account_id' => $shrinkageGl->id, 'debit'  => $cost,
                 'description' => "Inventory shrinkage — {$description}"],
                ['account_id' => $inventoryGl->id, 'credit' => $cost,
                 'description' => "Inventory reduced — {$description}"],
            ],
            entryDate: now()->toDateTime(),
            branchId:  $branch?->id,
            actor:     $actor,
        );
    }
}