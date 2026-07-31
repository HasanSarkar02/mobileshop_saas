<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case Pending            = 'pending';
    case Promised           = 'promised';
    case RequestedMoreTime  = 'requested_more_time';
    case NoResponse         = 'no_response';
    case PhoneSwitchedOff   = 'phone_switched_off';
    case PartiallyPaid      = 'partially_paid';
    case Paid               = 'paid';
    case Cancelled          = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending           => 'Pending',
            self::Promised          => 'Promised',
            self::RequestedMoreTime => 'Requested More Time',
            self::NoResponse        => 'No Response',
            self::PhoneSwitchedOff  => 'Phone Switched Off',
            self::PartiallyPaid     => 'Partially Paid',
            self::Paid              => 'Paid',
            self::Cancelled         => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Paid              => 'badge-green',
            self::Promised          => 'badge-blue',
            self::PartiallyPaid     => 'badge-indigo',
            self::Cancelled         => 'badge-gray',
            self::NoResponse, self::PhoneSwitchedOff => 'badge-yellow',
            default                 => 'badge-gray',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Paid, self::Cancelled], true);
    }
}