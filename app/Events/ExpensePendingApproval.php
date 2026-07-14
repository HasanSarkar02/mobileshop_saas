<?php

namespace App\Events;

use App\Models\Expense;
use App\Models\Shop;

class ExpensePendingApproval
{
    public function __construct(
        public readonly Expense $expense,
        public readonly Shop $shop,
    ) {}
}