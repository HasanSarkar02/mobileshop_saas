<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case Draft     = 'draft';
    case Completed = 'completed';
    case Rejected  = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Completed => 'Completed',
            self::Rejected  => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-yellow',
            self::Completed => 'badge-green',
            self::Rejected  => 'badge-red',
        };
    }
}