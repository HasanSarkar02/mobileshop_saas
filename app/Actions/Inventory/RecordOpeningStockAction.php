<?php

namespace App\Actions\Inventory;

use App\Enums\ProductTrackingType;
use App\Enums\UnitStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\JournalEntry;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class RecordOpeningStockAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    /**
     * Non-serialized: set quantity + average cost on branch_stocks.
     * Creates GL entry: Dr Inventory (1200) / Cr Opening Balance Equity (3020)
     */
    public function executeNonSerialized(
        Shop           $shop,
        ProductVariant $variant,
        Branch         $branch,
        float          $quantity,
        float          $unitCost,
        User           $actor,
    ): void {
        if ($quantity <= 0) throw new \RuntimeException('Quantity must be greater than zero.');
        if ($unitCost < 0)  throw new \RuntimeException('Unit cost cannot be negative.');

        DB::transaction(function () use ($shop, $variant, $branch, $quantity, $unitCost, $actor) {
            $totalCost = $quantity * $unitCost;

            // Upsert branch stock — weighted average if already has stock
            $existing = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('branch_id', $branch->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $newQty  = $existing->quantity + $quantity;
                $newCost = $newQty > 0
                    ? (($existing->quantity * $existing->average_cost) + $totalCost) / $newQty
                    : $unitCost;

                $existing->update(['quantity' => $newQty, 'average_cost' => $newCost]);
            } else {
                BranchStock::create([
                    'shop_id'            => $shop->id,
                    'branch_id'          => $branch->id,
                    'product_variant_id' => $variant->id,
                    'quantity'           => $quantity,
                    'average_cost'       => $unitCost,
                ]);
            }

            // GL entry only if cost > 0 — link it to the audit row so it can be reversed later
            $journalEntry = $totalCost > 0
                ? $this->postOpeningStockJournal($shop, $branch, $totalCost, $variant->sku, $actor)
                : null;

            // Audit log
            StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $branch->id,
                'product_variant_id' => $variant->id,
                'adjustment_type'    => 'opening_stock',
                'quantity'           => $quantity,
                'unit_cost'          => $unitCost,
                'total_cost'         => $totalCost,
                'reason'             => 'Opening stock entry',
                'created_by'         => $actor->id,
                'journal_entry_id'   => $journalEntry?->id,
            ]);
        });
    }

    /**
     * Serialized: register each IMEI as a ProductUnit.
     * The StockAdjustment (batch) row is created BEFORE the units so every
     * unit can be stamped with stock_adjustment_id — this is what makes a
     * later batch reversal possible.
     */
    public function executeSerialized(
        Shop           $shop,
        ProductVariant $variant,
        Branch         $branch,
        array          $serials,   // [['serial_number'=>'', 'secondary_serial_number'=>'', 'cost_price'=>0]]
        User           $actor,
    ): int {
        if (empty($serials)) throw new \RuntimeException('At least one IMEI is required.');

        return DB::transaction(function () use ($shop, $variant, $branch, $serials, $actor) {
            // Pass 1 — validate & dedupe without touching the database, so we
            // know the final totals before creating anything.
            $toCreate  = [];
            $totalCost = 0.0;

            foreach ($serials as $row) {
                $serial = trim($row['serial_number'] ?? '');
                if (empty($serial)) continue;

                $exists = ProductUnit::withoutGlobalScopes()
                    ->where('serial_number', $serial)
                    ->where('is_archived', false)
                    ->exists();

                if ($exists) continue;

                $cost = (float) ($row['cost_price'] ?? 0);

                $toCreate[] = [
                    'serial_number'                => $serial,
                    'secondary_serial_number'      => $row['secondary_serial_number'] ?? null,
                    'cost_price'                   => $cost,
                    'manufacturer_warranty_months' => (int) ($row['warranty_months'] ?? 0),
                    'shop_warranty_days'           => (int) ($row['shop_warranty_days'] ?? 0),
                ];

                $totalCost += $cost;
            }

            $created = count($toCreate);

            $journalEntry = $totalCost > 0
                ? $this->postOpeningStockJournal($shop, $branch, $totalCost, $variant->sku, $actor)
                : null;

            $adjustment = StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $branch->id,
                'product_variant_id' => $variant->id,
                'adjustment_type'    => 'opening_stock',
                'quantity'           => $created,
                'unit_cost'          => $created > 0 ? $totalCost / $created : 0,
                'total_cost'         => $totalCost,
                'reason'             => 'Opening stock entry',
                'created_by'         => $actor->id,
                'journal_entry_id'   => $journalEntry?->id,
            ]);

            // Pass 2 — now create the units, each traceable back to $adjustment
            foreach ($toCreate as $unitData) {
                ProductUnit::create([
                    'shop_id'                      => $shop->id,
                    'branch_id'                    => $branch->id,
                    'product_variant_id'           => $variant->id,
                    'stock_adjustment_id'          => $adjustment->id,
                    'serial_number'                => $unitData['serial_number'],
                    'secondary_serial_number'      => $unitData['secondary_serial_number'],
                    'cost_price'                   => $unitData['cost_price'],
                    'status'                       => UnitStatus::InStock,
                    'is_archived'                  => false,
                    'manufacturer_warranty_months' => $unitData['manufacturer_warranty_months'],
                    'shop_warranty_days'           => $unitData['shop_warranty_days'],
                    'purchase_line_item_id'        => null, // opening stock, no purchase
                ]);
            }

            return $created;
        });
    }

    private function postOpeningStockJournal(
        Shop   $shop,
        Branch $branch,
        float  $cost,
        string $sku,
        User   $actor,
    ): JournalEntry {
        $inventoryGl = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();

        $openingEquity = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)->where('code', '3020')->firstOrFail();

        return $this->accounting->postEntry(
            shop:        $shop,
            description: "Opening stock — {$sku}",
            lines: [
                ['account_id' => $inventoryGl->id,    'debit'  => $cost, 'description' => "Opening stock: {$sku}"],
                ['account_id' => $openingEquity->id,  'credit' => $cost, 'description' => "Opening balance equity"],
            ],
            branchId: $branch->id,
            actor:    $actor,
        );
    }
}