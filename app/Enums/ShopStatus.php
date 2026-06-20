<?php

namespace App\Enums;

enum ShopStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
}