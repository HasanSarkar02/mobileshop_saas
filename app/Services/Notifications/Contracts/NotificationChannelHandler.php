<?php

namespace App\Services\Notifications\Contracts;

use App\Models\NotificationDelivery;

interface NotificationChannelHandler
{
    /**
     * Attempt delivery and update $delivery's own status/attempts/
     * error_message/sent_at/provider_reference. Implementations must NEVER
     * throw — a misbehaving channel must not be able to break the dispatch
     * pipeline. This is the same non-blocking posture SmsService::send()
     * already takes for the existing SMS module; every channel handler here
     * follows it too. SendNotificationChannelJob adds a final catch-all as a
     * last line of defense, but handlers should not rely on that.
     */
    public function send(NotificationDelivery $delivery): void;
}