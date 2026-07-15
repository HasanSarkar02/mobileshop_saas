<?php
namespace App\Events;

use App\Models\PurchaseReturn;
use App\Models\Shop;

class PurchaseReturnProcessed
{
    public function __construct(public readonly PurchaseReturn $purchaseReturn, public readonly Shop $shop) {}
}