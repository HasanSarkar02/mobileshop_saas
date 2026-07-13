<?php

namespace App\Enums;

enum NotificationDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Skipped => 'Skipped',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge-gray',
            self::Sent, self::Delivered => 'badge-green',
            self::Failed => 'badge-red',
            self::Skipped => 'badge-yellow',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Sent, self::Delivered, self::Failed, self::Skipped], true);
    }
}