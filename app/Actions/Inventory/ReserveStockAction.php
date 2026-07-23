<?php
namespace App\Actions\Inventory;

use App\Enums\UnitStatus;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\UnitStatusTransitioner;
use Carbon\Carbon;
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
        ?string        $heldForName = null,
        ?string        $heldForPhone = null,
        ?Carbon        $holdExpiresAt = null,
    ): StockAdjustment {
        return DB::transaction(function () use ($shop, $variant, $branchId, $quantity, $reason, $actor, $heldForName, $heldForPhone, $holdExpiresAt) {
            $stock = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('branch_id', $branchId)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->available_quantity < $quantity) {
                throw new \RuntimeException("Cannot reserve. Available: " . ($stock?->available_quantity ?? 0));
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
                'held_for_name'      => $heldForName,
                'held_for_phone'     => $heldForPhone,
                'hold_expires_at'    => $holdExpiresAt,
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

    public function reserveSerialized(
        Shop        $shop,
        ProductUnit $unit,
        string      $reason,
        User        $actor,
        ?string     $heldForName = null,
        ?string     $heldForPhone = null,
        ?Carbon     $holdExpiresAt = null,
    ): StockAdjustment {
        if ($unit->status !== UnitStatus::InStock) {
            throw new \RuntimeException("Cannot reserve unit [{$unit->serial_number}] — current status: {$unit->status->label()}.");
        }

        return DB::transaction(function () use ($shop, $unit, $reason, $actor, $heldForName, $heldForPhone, $holdExpiresAt) {
            app(UnitStatusTransitioner::class)->transition($unit, UnitStatus::Reserved);

            return StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $unit->branch_id,
                'product_variant_id' => $unit->product_variant_id,
                'product_unit_id'    => $unit->id,
                'adjustment_type'    => 'reserved',
                'quantity'           => 1,
                'unit_cost'          => 0,
                'total_cost'         => 0,
                'reason'             => $reason,
                'held_for_name'      => $heldForName,
                'held_for_phone'     => $heldForPhone,
                'hold_expires_at'    => $holdExpiresAt,
                'created_by'         => $actor->id,
            ]);
        });
    }

    public function releaseSerialized(Shop $shop, ProductUnit $unit, string $reason, User $actor): StockAdjustment
    {
        if ($unit->status !== UnitStatus::Reserved) {
            throw new \RuntimeException("Unit [{$unit->serial_number}] is not currently reserved.");
        }

        return DB::transaction(function () use ($shop, $unit, $reason, $actor) {
            app(UnitStatusTransitioner::class)->transition($unit, UnitStatus::InStock);

            return StockAdjustment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $unit->branch_id,
                'product_variant_id' => $unit->product_variant_id,
                'product_unit_id'    => $unit->id,
                'adjustment_type'    => 'unreserved',
                'quantity'           => 1,
                'unit_cost'          => 0,
                'total_cost'         => 0,
                'reason'             => $reason,
                'created_by'         => $actor->id,
            ]);
        });
    }
}