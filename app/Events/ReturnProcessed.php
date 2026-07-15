<?php
namespace App\Events;

use App\Models\CreditNote;
use App\Models\Shop;

class ReturnProcessed
{
    public function __construct(public readonly CreditNote $creditNote, public readonly Shop $shop) {}
}