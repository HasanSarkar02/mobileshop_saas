<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Pending  = 'pending';   // awaiting owner approval
    case Approved = 'approved';  // journal entry posted
    case Rejected = 'rejected';  // no journal posted
    case Voided   = 'voided';    // reversal journal posted

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending  => 'badge-yellow',
            self::Approved => 'badge-green',
            self::Rejected => 'badge-red',
            self::Voided   => 'badge-gray',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Voided   => 'Voided',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Pending;
    }
}