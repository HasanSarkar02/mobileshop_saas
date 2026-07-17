<?php

namespace App\Actions;

use App\Events\StockTransferReceived;
use App\Models\BranchStock;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConfirmStockTransferAction
{
    public function execute(StockTransfer $transfer, User $actor): StockTransfer
    {
        if ($transfer->status !== 'in_transit') {
            throw new RuntimeException('Only an in-transit transfer can be confirmed.');
        }

        return DB::transaction(function () use ($transfer, $actor) {
            foreach ($transfer->items as $item) {
                if ($item->product_unit_id) {
                    $item->productUnit->update(['branch_id' => $transfer->to_branch_id]);
                } else {
                    $stock = BranchStock::firstOrCreate(
                        ['shop_id' => $transfer->shop_id, 'branch_id' => $transfer->to_branch_id, 'product_variant_id' => $item->product_variant_id],
                        ['quantity' => 0, 'average_cost' => 0]
                    );

                    $stock->increment('quantity', $item->quantity);
                }
            }

            $transfer->update(['status' => 'received', 'confirmed_by' => $actor->id, 'confirmed_at' => now()]);
            $shop = Shop::withoutGlobalScopes()->findOrFail($transfer->shop_id);
            DB::afterCommit(fn () => event(new StockTransferReceived($transfer, $shop)));
            return $transfer->fresh('items');
        });
    }
}