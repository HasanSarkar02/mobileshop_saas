<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\NotificationChannelHandler;

/**
 * Phase 4. No Firebase provider selected/configured yet — not invented here.
 *
 * The schema is already shaped so wiring this in later needs NO migration:
 *   - notifications.payload is a free-form JSON bag; flatten it into FCM's
 *     string-keyed data map at send-time inside this handler.
 *   - notifications.reference_type/reference_id already give the mobile
 *     client everything it needs to deep-link natively — it never needs
 *     DeepLinkResolver's web route names.
 *   - A future `user_push_tokens` table (device token registry) is the only
 *     new table this will need; NotificationDelivery already has a
 *     provider_reference column ready to hold the FCM message id.
 */
class PushChannelHandler implements NotificationChannelHandler
{
    public function send(NotificationDelivery $delivery): void
    {
        $delivery->update([
            'status' => NotificationDeliveryStatus::Skipped->value,
            'error_message' => 'Push channel has no provider configured yet (Phase 4).',
        ]);
    }
}