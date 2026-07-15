<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Models\Shop;

interface ReminderCheckerInterface
{
    public function check(Shop $shop): void;
}