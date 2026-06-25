<?php

namespace App\Enums;

enum PhoneCondition: string
{
    case Excellent = 'excellent';
    case Good      = 'good';
    case Fair      = 'fair';
    case Poor      = 'poor';
    case ForParts  = 'for_parts';

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Excellent (like new)',
            self::Good      => 'Good (minor scratches)',
            self::Fair      => 'Fair (visible wear)',
            self::Poor      => 'Poor (heavy wear/cracks)',
            self::ForParts  => 'For parts only',
        };
    }
}