<?php
namespace App\Events;

use App\Models\ProductVariant;
use App\Models\Shop;

class StockLow
{
    public function __construct(
        public readonly ProductVariant $variant,
        public readonly int $branchId,
        public readonly int $remainingQuantity,
        public readonly Shop $shop,
    ) {}
}