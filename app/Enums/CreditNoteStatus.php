<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case Draft     = 'draft';
    case Completed = 'completed';
    case Rejected  = 'rejected';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-yellow',
            self::Completed => 'badge-green',
            self::Rejected  => 'badge-red',
        };
    }
}