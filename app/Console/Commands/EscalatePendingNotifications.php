<?php

namespace App\Console\Commands;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Shop;
use App\Services\Notifications\NotificationChannelManager;
use App\Services\Notifications\RecipientResolver;
use Illuminate\Console\Command;

class EscalatePendingNotifications extends Command
{
    protected $signature = 'notifications:escalate';
    protected $description = 'Bump priority and re-notify the Owner for action-required notifications no one has acted on within their priority window.';

    private const MAX_ESCALATION_LEVEL = 3;

    public function handle(RecipientResolver $recipients, NotificationChannelManager $channels): void
    {
        $escalated = 0;

        Notification::withoutGlobalScopes()
            ->where('action_required', true)
            ->where('escalation_level', '<', self::MAX_ESCALATION_LEVEL)
            ->whereHas('recipients', fn ($q) => $q->whereNull('read_at')
                ->whereNull('dismissed_at')
                ->whereNull('action_taken_at')
                ->where(fn ($sq) => $sq->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now())))
            ->chunkById(100, function ($notifications) use (&$escalated, $recipients, $channels) {
                foreach ($notifications as $notification) {
                    $windowMinutes = $notification->priority->escalationWindowMinutes();

                    if ($windowMinutes === null) {
                        continue;
                    }

                    $since = $notification->escalated_at ?? $notification->created_at;

                    if ($since->diffInMinutes(now()) < $windowMinutes) {
                        continue;
                    }

                    $shop = Shop::withoutGlobalScopes()->find($notification->shop_id);

                    if (! $shop) {
                        continue;
                    }

                    $this->notifyOwner($notification, $shop, $recipients, $channels);

                    $notification->update([
                        'priority' => $notification->priority->nextLevel()->value,
                        'escalation_level' => $notification->escalation_level + 1,
                        'escalated_at' => now(),
                    ]);

                    $escalated++;
                }
            });

        $this->info("Escalated {$escalated} notification(s).");
    }

    private function notifyOwner(
        Notification $notification,
        Shop $shop,
        RecipientResolver $recipients,
        NotificationChannelManager $channels,
    ): void {
        $owner = $recipients->owner($shop)->first();

        if (! $owner) {
            return;
        }

        $recipient = $notification->recipients()->where('user_id', $owner->id)->first();

        if ($recipient) {
            $recipient->update(['read_at' => null, 'dismissed_at' => null, 'snoozed_until' => null]);
        } else {
            $recipient = $notification->recipients()->create([
                'user_id' => $owner->id,
                'shop_id' => $shop->id,
                'delivered_at' => now(),
            ]);
        }

        $delivery = NotificationDelivery::firstOrCreate(
            ['notification_recipient_id' => $recipient->id, 'channel' => NotificationChannel::Email->value],
            ['status' => NotificationDeliveryStatus::Pending->value]
        );

        $channels->handlerFor(NotificationChannel::Email)->send($delivery);
    }
}