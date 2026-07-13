<?php

namespace App\Enums;

enum PayrollRunStatus: string
{
    case Draft             = 'draft';
    case UnderReview       = 'under_review';
    case Approved          = 'approved';
    case ProcessingPayment = 'processing_payment';
    case PartiallyPaid     = 'partially_paid';
    case Paid              = 'paid';
    case Cancelled         = 'cancelled';
    case Reversed          = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft             => 'Draft',
            self::UnderReview       => 'Under Review',
            self::Approved          => 'Approved',
            self::ProcessingPayment => 'Processing Payment',
            self::PartiallyPaid     => 'Partially Paid',
            self::Paid              => 'Paid',
            self::Cancelled         => 'Cancelled',
            self::Reversed          => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft             => 'badge-gray',
            self::UnderReview       => 'badge-yellow',
            self::Approved          => 'badge-blue',
            self::ProcessingPayment => 'badge-indigo',
            self::PartiallyPaid     => 'badge-amber',
            self::Paid              => 'badge-green',
            self::Cancelled         => 'badge-red',
            self::Reversed          => 'badge-gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled, self::Reversed]);
    }

    public function canBeApproved(): bool
    {
        return $this === self::UnderReview;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Draft, self::UnderReview]);
    }

    public function canAcceptPayments(): bool
    {
        return in_array($this, [
            self::Approved,
            self::ProcessingPayment,
            self::PartiallyPaid,
        ]);
    }
}