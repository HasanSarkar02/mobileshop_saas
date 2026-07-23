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
use App\Services\UnitStatusTransitioner;
use Illuminate\Support\Facades\DB;

class MarkStockDamagedAction
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly UnitStatusTransitioner $transitioner,
        ) {}

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

            activity()
                ->causedBy($actor)
                ->performedOn($adjustment)
                ->withProperties([
                    'type' => 'damaged',
                    'tracking_type' => 'non_serialized',
                    'quantity' => $quantity,
                    'branch_id' => $branch->id,
                    'reason' => $reason
                ])
                ->log('inventory.stock_damaged');
            return $adjustment;

        });
    }

    /**
     * Mark a serialized unit (IMEI) as damaged.
     */
    public function executeSerialized(
        Shop $shop, ProductUnit $unit, string $reason, User $actor,
    ): StockAdjustment {
        return DB::transaction(function () use ($shop, $unit, $reason, $actor) {
            $lockedUnit = $this->transitioner->markDamaged($unit->id);

            $totalCost = (float) $lockedUnit->cost_price;

            $journal = $this->postShrinkageJournal(
                $shop,
                $lockedUnit->branch,
                $totalCost,
                "IMEI: {$lockedUnit->serial_number}",
                $actor
            );

            $adjustment = StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $lockedUnit->branch_id,
                'product_variant_id' => $lockedUnit->product_variant_id,
                'product_unit_id'    => $lockedUnit->id,
                'adjustment_type'    => 'damaged',
                'quantity'           => 1,
                'unit_cost'          => $totalCost,
                'total_cost'         => $totalCost,
                'reason'             => $reason,
                'journal_entry_id'   => $journal->id,
                'created_by'         => $actor->id,
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($adjustment)
                ->withProperties([
                    'type' => 'damaged',
                    'tracking_type' => 'serialized',
                    'imei' => $lockedUnit->serial_number,
                    'quantity' => 1,
                    'branch_id' => $lockedUnit->branch_id,
                    'reason' => $reason
                ])
                ->log('inventory.stock_damaged');

            return $adjustment;
        });
    }

    private function postShrinkageJournal(
        Shop   $shop,
        Branch|null $branch,
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