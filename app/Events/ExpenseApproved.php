<?php

namespace App\Events;

use App\Models\Expense;
use App\Models\Shop;
use App\Models\User;

class ExpenseApproved
{
    public function __construct(
        public readonly Expense $expense,
        public readonly Shop $shop,
        public readonly User $actor,
    ) {}
}