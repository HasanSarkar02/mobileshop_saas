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

class WriteOffStockAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    /**
     * Write off non-serialized stock.
     * If stock was already marked damaged → only reduces damaged_quantity (journal already posted).
     * If writing off directly → posts full journal.
     */
    public function executeNonSerialized(
        Shop           $shop,
        ProductVariant $variant,
        Branch         $branch,
        float          $quantity,
        string         $reason,
        bool           $alreadyDamaged = false,
        User           $actor,
    ): StockAdjustment {
        return DB::transaction(function () use ($shop, $variant, $branch, $quantity, $reason, $alreadyDamaged, $actor) {
            $stock = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('branch_id', $branch->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new \RuntimeException('No stock record found for this product in this branch.');
            }

            $journalEntry = null;
            $unitCost     = (float) $stock->average_cost;
            $totalCost    = $unitCost * $quantity;

            if ($alreadyDamaged) {
                // Was already marked damaged → already removed from quantity, reduce damaged_quantity
                if ($stock->damaged_quantity < $quantity) {
                    throw new \RuntimeException("Insufficient damaged quantity to write off.");
                }
                $stock->decrement('damaged_quantity', $quantity);
                // Journal already posted when marked damaged
            } else {
                // Direct write-off from available stock
                if ($stock->available_quantity < $quantity) {
                    throw new \RuntimeException("Insufficient available stock.");
                }
                $stock->decrement('quantity', $quantity);

                $journalEntry = $this->postWriteOffJournal(
                    $shop, $branch, $totalCost, $variant->name, $actor
                );
            }

            return StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $branch->id,
                'product_variant_id' => $variant->id,
                'adjustment_type'    => 'written_off',
                'quantity'           => $quantity,
                'unit_cost'          => $unitCost,
                'total_cost'         => $totalCost,
                'reason'             => $reason,
                'journal_entry_id'   => $journalEntry?->id,
                'created_by'         => $actor->id,
            ]);
        });
    }

    /**
     * Write off a serialized unit.
     * Posts journal and changes unit status to WrittenOff.
     */
    public function executeSerialized(
        Shop        $shop,
        ProductUnit $unit,
        string      $reason,
        User        $actor,
    ): StockAdjustment {
        $allowedStatuses = [
            \App\Enums\UnitStatus::InStock,
            \App\Enums\UnitStatus::Damaged,
            \App\Enums\UnitStatus::Lost,
        ];

        if (! in_array($unit->status, $allowedStatuses)) {
            throw new \RuntimeException("Cannot write off a unit with status: {$unit->status->label()}");
        }

        return DB::transaction(function () use ($shop, $unit, $reason, $actor) {
            $alreadyDamaged = $unit->status === \App\Enums\UnitStatus::Damaged;
            $totalCost      = (float) $unit->cost_price;

            $unit->update(['status' => \App\Enums\UnitStatus::WrittenOff]);

            // Only post GL if not already posted when damaged
            $journalEntry = null;
            if (! $alreadyDamaged) {
                $journalEntry = $this->postWriteOffJournal(
                    $shop, $unit->branch, $totalCost, "IMEI: {$unit->serial_number}", $actor
                );
            }

            return StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $unit->branch_id,
                'product_variant_id' => $unit->product_variant_id,
                'product_unit_id'    => $unit->id,
                'adjustment_type'    => 'written_off',
                'quantity'           => 1,
                'unit_cost'          => $totalCost,
                'total_cost'         => $totalCost,
                'reason'             => $reason,
                'journal_entry_id'   => $journalEntry?->id,
                'created_by'         => $actor->id,
            ]);
        });
    }

    private function postWriteOffJournal(
        Shop   $shop,
        ?Branch $branch,
        float  $cost,
        string $description,
        User   $actor,
    ): \App\Models\JournalEntry {
        $shrinkageGl = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)->where('code', '6040')->firstOrFail();

        $inventoryGl = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();

        return $this->accounting->postEntry(
            shop:        $shop,
            description: "Inventory write-off — {$description}",
            lines: [
                ['account_id' => $shrinkageGl->id, 'debit'  => $cost,
                 'description' => "Write-off — {$description}"],
                ['account_id' => $inventoryGl->id, 'credit' => $cost,
                 'description' => "Inventory write-off — {$description}"],
            ],
            entryDate: now()->toDateTime(),
            branchId:  $branch?->id,
            actor:     $actor,
        );
    }
}