<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Shop;

class CustomerPaymentRecorded
{
    public function __construct(
        public readonly CustomerTransaction $transaction,
        public readonly Customer $customer,
        public readonly Shop $shop,
    ) {}
}