<?php
namespace App\Events;

use App\Models\Shop;
use App\Models\User;

class SalaryOverdrawn
{
    public function __construct(
        public readonly User $employee,
        public readonly float $overdrawAmount,
        public readonly Shop $shop,
    ) {}
}