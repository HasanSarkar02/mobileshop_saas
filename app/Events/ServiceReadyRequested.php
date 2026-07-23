<?php

namespace App\Events;

use App\Models\ServiceTicket;
use App\Models\Shop;

class ServiceReadyRequested
{
    public function __construct(
        public readonly Shop $shop,
        public readonly ServiceTicket $ticket,
    ) {}
}