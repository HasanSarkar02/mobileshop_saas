<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Draft     = 'draft';
    case Confirmed = 'confirmed';
    case Voided    = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Confirmed => 'Confirmed',
            self::Voided    => 'Voided',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-gray',
            self::Confirmed => 'badge-green',
            self::Voided    => 'badge-red',
        };
    }
}