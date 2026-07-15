<?php
namespace App\Events;

use App\Models\FinancePartnerSettlement;
use App\Models\Shop;

class FpSettlementRecorded
{
    public function __construct(public readonly FinancePartnerSettlement $settlement, public readonly Shop $shop) {}
}