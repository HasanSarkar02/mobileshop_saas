<?php

namespace App\Events;

use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;

class SaleVoided
{
    public function __construct(
        public readonly Sale $sale,
        public readonly Shop $shop,
        public readonly User $actor,
        public readonly string $reason,
    ) {}
}