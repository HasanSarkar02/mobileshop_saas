<?php

namespace App\Enums;

enum ReturnCondition: string
{
    case Good        = 'good';
    case MinorDefect = 'minor_defect';
    case Defective   = 'defective';
    case Incomplete  = 'incomplete'; // missing accessories

    public function label(): string
    {
        return match ($this) {
            self::Good        => 'Good condition',
            self::MinorDefect => 'Minor defect',
            self::Defective   => 'Defective / not working',
            self::Incomplete  => 'Incomplete (missing accessories)',
        };
    }

    public function shouldRestock(): bool
    {
        return in_array($this, [self::Good, self::MinorDefect]);
    }
}