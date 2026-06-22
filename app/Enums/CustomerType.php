<?php

namespace App\Enums;

enum CustomerType: string
{
    case WalkIn    = 'walk_in';
    case Regular   = 'regular';
    case Credit    = 'credit';    // buys on baki/due
    case Wholesale = 'wholesale';
    case Vip       = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::WalkIn    => 'Walk-in',
            self::Regular   => 'Regular',
            self::Credit    => 'Credit / Baki',
            self::Wholesale => 'Wholesale',
            self::Vip       => 'VIP',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::WalkIn    => 'badge-gray',
            self::Regular   => 'badge-blue',
            self::Credit    => 'badge-yellow',
            self::Wholesale => 'badge-green',
            self::Vip       => 'badge-red',
        };
    }
}