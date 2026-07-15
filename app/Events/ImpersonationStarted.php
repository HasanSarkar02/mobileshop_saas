<?php
namespace App\Events;

use App\Models\Shop;
use App\Models\User;

class ImpersonationStarted
{
    public function __construct(
        public readonly User $target,
        public readonly User $superAdmin,
        public readonly Shop $shop,
    ) {}
}