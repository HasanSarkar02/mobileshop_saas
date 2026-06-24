<?php

namespace App\Enums;

enum DiscountType: string
{
    case None       = 'none';
    case Percentage = 'percentage';
    case Flat       = 'flat';
}