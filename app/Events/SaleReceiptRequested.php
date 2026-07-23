<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Shop;

class SaleReceiptRequested
{
    public function __construct(
        public readonly Shop $shop,
        public readonly Sale $sale,
        public readonly ?Customer $customer,
    ) {}
}