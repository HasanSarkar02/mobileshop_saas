<?php

namespace App\Enums;

enum FollowUpType: string
{
    case PhoneCall = 'phone_call';
    case WhatsApp  = 'whatsapp';
    case Visit     = 'visit';
    case Sms       = 'sms';
    case Email     = 'email';
    case Other     = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PhoneCall => 'Phone Call',
            self::WhatsApp  => 'WhatsApp',
            self::Visit     => 'Visit',
            self::Sms       => 'SMS',
            self::Email     => 'Email',
            self::Other     => 'Other',
        };
    }
}