<?php

namespace App\Events;

use App\Models\Shop;
use App\Models\TreasuryTransaction;
use App\Models\User;

class TreasuryApproved
{
    public function __construct(
        public readonly TreasuryTransaction $transaction,
        public readonly Shop $shop,
        public readonly User $actor,
    ) {}
}