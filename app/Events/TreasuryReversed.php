<?php
namespace App\Events;

use App\Models\Shop;
use App\Models\TreasuryTransaction;
use App\Models\User;

class TreasuryReversed
{
    public function __construct(
        public readonly TreasuryTransaction $original,
        public readonly TreasuryTransaction $reversal,
        public readonly Shop $shop,
        public readonly User $actor,
    ) {}
}