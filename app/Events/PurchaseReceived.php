<?php
namespace App\Events;

use App\Models\Purchase;
use App\Models\Shop;

class PurchaseReceived
{
    public function __construct(public readonly Purchase $purchase, public readonly Shop $shop) {}
}