<?php

namespace App\Actions;

use App\Enums\UnitStatus;
use App\Events\StockTransferInitiated;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InitiateStockTransferAction
{
    /**
     * @param  array{from_branch_id:int, to_branch_id:int, items: array<int, array{product_variant_id:int, product_unit_id?:int, quantity?:int}>}  $data
     */
    public function execute(Shop $shop, array $data, User $actor): StockTransfer
    {
        if ($data['from_branch_id'] === $data['to_branch_id']) {
            throw new InvalidArgumentException('Source and destination branch must be different.');
        }

        return DB::transaction(function () use ($shop, $data, $actor) {
            $transfer = StockTransfer::create([
                'shop_id' => $shop->id,
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'status' => 'in_transit',
                'initiated_by' => $actor->id,
                'initiated_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                if (! empty($item['product_unit_id'])) {
                    $unit = ProductUnit::lockForUpdate()->findOrFail($item['product_unit_id']);

                    if ($unit->status !== UnitStatus::InStock || $unit->branch_id !== $data['from_branch_id']) {
                        throw new InvalidArgumentException("Unit [{$unit->serial_number}] is not available to transfer from this branch.");
                    }

                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_variant_id' => $unit->product_variant_id,
                        'product_unit_id' => $unit->id,
                        'quantity' => 1,
                    ]);
                } else {
                    $stock = BranchStock::where('branch_id', $data['from_branch_id'])
                        ->where('product_variant_id', $item['product_variant_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($stock->quantity < $item['quantity']) {
                        throw new InvalidArgumentException('Not enough stock at the source branch for this transfer.');
                    }

                    $stock->decrement('quantity', $item['quantity']);

                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
            DB::afterCommit(fn () => event(new StockTransferInitiated($transfer, $shop)));

            return $transfer->fresh('items');
        });
    }
}