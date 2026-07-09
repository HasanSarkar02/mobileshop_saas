<?php

namespace App\Enums;

enum TreasuryTransactionStatus: string
{
    case Draft           = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved        = 'approved';
    case Completed       = 'completed';
    case Rejected        = 'rejected';
    case Reversed        = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Approved        => 'Approved',
            self::Completed       => 'Completed',
            self::Rejected        => 'Rejected',
            self::Reversed        => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft           => 'badge-gray',
            self::PendingApproval => 'badge-yellow',
            self::Approved        => 'badge-blue',
            self::Completed       => 'badge-green',
            self::Rejected        => 'badge-red',
            self::Reversed        => 'badge-gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Reversed]);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canBeReversed(): bool
    {
        return $this === self::Completed;
    }
}