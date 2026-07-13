<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\NotificationChannelHandler;

/**
 * This stack has no websocket/broadcast layer (confirmed: app_blade.php and
 * admin_blade.php both use a plain Alpine `@notify.window` listener fed by
 * Livewire's `$this->dispatch('notify', [...])`, not Echo/Reverb/Pusher).
 * "Popup delivery" here means the row is flagged delivered, and
 * NotificationBell's own wire:poll cycle detects it as new and fires that
 * exact same 'notify' browser event to actually pop a toast — see
 * NotificationBell::refresh() / the bell view.
 *
 * If a broadcast layer is added later, only this handler's internals need to
 * change to a broadcast() call — nothing upstream (dispatcher, jobs, storage)
 * needs to change.
 */
class PopupChannelHandler implements NotificationChannelHandler
{
    public function send(NotificationDelivery $delivery): void
    {
        $delivery->update([
            'status' => NotificationDeliveryStatus::Delivered->value,
            'sent_at' => now(),
        ]);
    }
}