<?php
namespace App\Actions\Inventory;

use App\Models\BranchStock;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReserveStockAction
{
    public function reserve(
        Shop           $shop,
        ProductVariant $variant,
        int            $branchId,
        float          $quantity,
        string         $reason,
        User           $actor,
    ): StockAdjustment {
        return DB::transaction(function () use ($shop, $variant, $branchId, $quantity, $reason, $actor) {
            $stock = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('branch_id', $branchId)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->available_quantity < $quantity) {
                throw new \RuntimeException(
                    "Cannot reserve. Available: " . ($stock?->available_quantity ?? 0)
                );
            }

            $stock->increment('reserved_quantity', $quantity);

            return StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $branchId,
                'product_variant_id' => $variant->id,
                'adjustment_type'    => 'reserved',
                'quantity'           => $quantity,
                'unit_cost'          => 0,
                'total_cost'         => 0,
                'reason'             => $reason,
                'created_by'         => $actor->id,
            ]);
        });
    }

    public function release(
        Shop           $shop,
        ProductVariant $variant,
        int            $branchId,
        float          $quantity,
        string         $reason,
        User           $actor,
    ): StockAdjustment {
        return DB::transaction(function () use ($shop, $variant, $branchId, $quantity, $reason, $actor) {
            $stock = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('branch_id', $branchId)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new \RuntimeException('Stock record not found.');
            }

            $release = min($quantity, (float) $stock->reserved_quantity);
            if ($release > 0) {
                $stock->decrement('reserved_quantity', $release);
            }

            return StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $branchId,
                'product_variant_id' => $variant->id,
                'adjustment_type'    => 'unreserved',
                'quantity'           => $release,
                'unit_cost'          => 0,
                'total_cost'         => 0,
                'reason'             => $reason,
                'created_by'         => $actor->id,
            ]);
        });
    }
}