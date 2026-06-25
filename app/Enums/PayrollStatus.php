<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft    = 'draft';
    case Approved = 'approved';
    case Paid     = 'paid';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft    => 'badge-gray',
            self::Approved => 'badge-blue',
            self::Paid     => 'badge-green',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Draft',
            self::Approved => 'Approved',
            self::Paid     => 'Paid',
        };
    }
}