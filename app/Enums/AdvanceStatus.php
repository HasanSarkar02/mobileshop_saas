<?php

namespace App\Enums;

enum AdvanceStatus: string
{
    case Active   = 'active';
    case FullyPaid = 'fully_paid';
    case Waived   = 'waived';
}