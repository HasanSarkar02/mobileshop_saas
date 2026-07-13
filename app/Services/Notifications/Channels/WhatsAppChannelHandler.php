<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\NotificationChannelHandler;

/** Reserved for the future WhatsApp channel. No provider selected yet. */
class WhatsAppChannelHandler implements NotificationChannelHandler
{
    public function send(NotificationDelivery $delivery): void
    {
        $delivery->update([
            'status' => NotificationDeliveryStatus::Skipped->value,
            'error_message' => 'WhatsApp channel is not yet available.',
        ]);
    }
}