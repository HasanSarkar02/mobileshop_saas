<?php

namespace App\Enums;


enum PayrollLoanStatus: string {
    case Active           = 'active';
    case FullyRecovered   = 'fully_recovered';
    case Waived           = 'waived';
    case Cancelled        = 'cancelled';
}