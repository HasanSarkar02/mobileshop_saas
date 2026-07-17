<?php
namespace App\Events;

use App\Models\PayrollRun;
use App\Models\Shop;
use App\Models\User;

class PayrollDraftReady
{
    public function __construct(public readonly PayrollRun $run, public readonly Shop $shop, public readonly User $actor) {}
}