<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\Shop;

class CustomerDueReminderRequested
{
    public function __construct(
        public readonly Shop $shop,
        public readonly Customer $customer,
    ) {}
}