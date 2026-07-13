<?php

namespace App\Enums;
enum ComponentCalculationType: string {
    case Fixed      = 'fixed';
    case Percentage = 'percentage';
    case Formula    = 'formula';
}