<?php
namespace App\Events;

use App\Models\Customer;
use App\Models\Shop;

class CustomerCreditLimitReached
{
    public function __construct(public readonly Customer $customer, public readonly Shop $shop) {}
}