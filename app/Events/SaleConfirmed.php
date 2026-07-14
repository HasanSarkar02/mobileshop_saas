<?php

namespace App\Events;

use App\Models\Sale;
use App\Models\Shop;

class SaleConfirmed
{
    public function __construct(
        public readonly Sale $sale,
        public readonly Shop $shop,
    ) {}
}