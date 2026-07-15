<?php

namespace App\Enums;

enum PushPlatform: string
{
    case Ios = 'ios';
    case Android = 'android';
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::Ios => 'iOS',
            self::Android => 'Android',
            self::Web => 'Web',
        };
    }
}