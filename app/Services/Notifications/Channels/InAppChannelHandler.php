<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\NotificationChannelHandler;

/**
 * There is no transport — the notification already exists as a row the
 * moment it was created (see NotificationDispatcher::fanOut()). This handler
 * exists purely so In-App participates in the same NotificationDelivery
 * bookkeeping as every other channel, for a uniform audit trail.
 */
class InAppChannelHandler implements NotificationChannelHandler
{
    public function send(NotificationDelivery $delivery): void
    {
        $delivery->update([
            'status' => NotificationDeliveryStatus::Delivered->value,
            'sent_at' => now(),
        ]);
    }
}