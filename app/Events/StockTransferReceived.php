<?php
namespace App\Events;

use App\Models\Shop;
use App\Models\StockTransfer;

class StockTransferReceived
{
    public function __construct(public readonly StockTransfer $transfer, public readonly Shop $shop) {}
}