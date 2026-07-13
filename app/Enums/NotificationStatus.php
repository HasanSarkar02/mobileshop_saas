<?php

namespace App\Enums;

/**
 * Lifecycle of the notification EVENT itself (not any one recipient's
 * engagement with it — that's read_at/dismissed_at/archived_at on
 * NotificationRecipient). Useful for ops/debugging: "did every channel
 * finish processing this?"
 */
enum NotificationStatus: string
{
    case Created = 'created';
    case Queued = 'queued';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Queued => 'Queued',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
        };
    }
}