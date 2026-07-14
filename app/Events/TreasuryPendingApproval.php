<?php

namespace App\Events;

use App\Models\Shop;
use App\Models\TreasuryTransaction;

class TreasuryPendingApproval
{
    public function __construct(
        public readonly TreasuryTransaction $transaction,
        public readonly Shop $shop,
    ) {}
}