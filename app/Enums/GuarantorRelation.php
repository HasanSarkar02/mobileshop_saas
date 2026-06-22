<?php

namespace App\Enums;

enum GuarantorRelation: string
{
    case Spouse    = 'spouse';
    case Parent    = 'parent';
    case Sibling   = 'sibling';
    case Friend    = 'friend';
    case Colleague = 'colleague';
    case Other     = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spouse    => 'Spouse',
            self::Parent    => 'Parent',
            self::Sibling   => 'Sibling',
            self::Friend    => 'Friend',
            self::Colleague => 'Colleague',
            self::Other     => 'Other',
        };
    }
}