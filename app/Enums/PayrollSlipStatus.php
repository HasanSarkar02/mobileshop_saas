<?php

namespace App\Enums;

enum PayrollSlipStatus: string
{
    case Draft          = 'draft';
    case Approved       = 'approved';
    case ReadyForPayment= 'ready_for_payment';
    case PartiallyPaid  = 'partially_paid';
    case Paid           = 'paid';
    case Cancelled      = 'cancelled';
    case Reversed       = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Draft',
            self::Approved        => 'Approved',
            self::ReadyForPayment => 'Ready for Payment',
            self::PartiallyPaid   => 'Partially Paid',
            self::Paid            => 'Paid',
            self::Cancelled       => 'Cancelled',
            self::Reversed        => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft           => 'badge-gray',
            self::Approved        => 'badge-blue',
            self::ReadyForPayment => 'badge-indigo',
            self::PartiallyPaid   => 'badge-yellow',
            self::Paid            => 'badge-green',
            self::Cancelled       => 'badge-red',
            self::Reversed        => 'badge-gray',
        };
    }

    public function canAcceptPayment(): bool
    {
        return in_array($this, [self::ReadyForPayment, self::PartiallyPaid]);
    }
}