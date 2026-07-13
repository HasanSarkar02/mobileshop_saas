<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Popup = 'popup';
    case Email = 'email';
    case Sms = 'sms';
    case Push = 'push';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::InApp => 'In-App',
            self::Popup => 'Popup',
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Push => 'Push Notification',
            self::WhatsApp => 'WhatsApp',
        };
    }

    /**
     * In-App and Popup are pure storage — the "delivery" is the row existing
     * and being picked up by the frontend on its next poll. Everything else
     * needs an outbound transport call and therefore gets queued.
     */
    public function requiresTransport(): bool
    {
        return match ($this) {
            self::InApp, self::Popup => false,
            self::Email, self::Sms, self::Push, self::WhatsApp => true,
        };
    }

    /** Which channels have a real, working handler in this phase. */
    public function isImplemented(): bool
    {
        return match ($this) {
            self::InApp, self::Popup, self::Sms => true,
            self::Email, self::Push, self::WhatsApp => false,
        };
    }
}