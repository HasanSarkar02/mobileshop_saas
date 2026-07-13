<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\NotificationChannelHandler;

/**
 * Phase 2. Deliberately not wired to Mail yet — the mailer strategy
 * (per-shop SMTP config, mirroring how SMS is per-shop provider/API key, vs.
 * a single platform-wide mailer) needs a decision before this is built, since
 * it changes the migration. Enabling the Email channel in user preferences
 * today degrades safely to "Skipped" with a clear reason instead of throwing,
 * so nothing breaks in the meantime — this also means EscalatePendingNotifications
 * calling this handler today is a safe no-op, not a failure.
 */
class EmailChannelHandler implements NotificationChannelHandler
{
    public function send(NotificationDelivery $delivery): void
    {
        $delivery->update([
            'status' => NotificationDeliveryStatus::Skipped->value,
            'error_message' => 'Email channel is not yet configured (Phase 2).',
        ]);
    }
}