<?php
namespace App\Events;

use App\Models\Shop;
use App\Models\UsedPhoneAcquisition;

class UsedPhoneAcquired
{
    public function __construct(public readonly UsedPhoneAcquisition $acquisition, public readonly Shop $shop) {}
}