<?php

namespace App\Enums;

enum FPReceivableStatus: string
{
    case Pending    = 'pending';
    case Partial    = 'partial';
    case Settled    = 'settled';
    case WrittenOff = 'written_off';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Partial    => 'Partially Settled',
            self::Settled    => 'Fully Settled',
            self::WrittenOff => 'Written Off',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending                    => 'badge-yellow',
            self::Partial                    => 'badge-blue',
            self::Settled                    => 'badge-green',
            self::WrittenOff, self::Cancelled => 'badge-red',
        };
    }
}