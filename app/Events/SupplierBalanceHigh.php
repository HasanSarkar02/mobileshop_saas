<?php
namespace App\Events;

use App\Models\Shop;
use App\Models\Supplier;

class SupplierBalanceHigh
{
    public function __construct(public readonly Supplier $supplier, public readonly Shop $shop) {}
}