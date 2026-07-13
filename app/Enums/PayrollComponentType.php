<?php

namespace App\Enums;

enum PayrollComponentType: string {
    case Earning   = 'earning';
    case Deduction = 'deduction';
}