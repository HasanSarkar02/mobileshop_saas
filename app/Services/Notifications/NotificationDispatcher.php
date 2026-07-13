<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEventType;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationChannelJob;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\NotificationRecipient;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationDispatcher
{
    public function __construct(private readonly DeepLinkResolver $deepLinks) {}

    /**
     * @param  Collection<int, User>  $recipients  Already-resolved audience — see RecipientResolver.
     *         Callers decide WHO; the dispatcher only knows HOW to fan out.
     * @param  array{
     *   title: string,
     *   body: string,
     *   reference?: Model|null,
     *   branch_id?: int|null,
     *   priority?: \App\Enums\NotificationPriority|null,
     *   action_required?: bool|null,
     *   action_label?: string|null,
     *   icon?: string|null,
     *   group_key?: string|null,
     *   group_cooldown_minutes?: int|null,
     *   payload?: array|null,
     *   created_by?: int|null,
     * }  $data
     */
    public function dispatch(
        NotificationEventType $eventType,
        Shop $shop,
        Collection $recipients,
        array $data
    ): ?Notification {
        if ($recipients->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($eventType, $shop, $recipients, $data) {
            $groupKey = $data['group_key'] ?? null;
            $cooldownMinutes = $data['group_cooldown_minutes'] ?? 30;

            if ($groupKey) {
                $existing = $this->reopenGroupedNotification($shop, $groupKey, $cooldownMinutes);

                if ($existing) {
                    return $existing;
                }
            }

            $reference = $data['reference'] ?? null;
            $priority = $data['priority'] ?? $eventType->defaultPriority();

            $notification = Notification::create([
                'shop_id' => $shop->id,
                'branch_id' => $data['branch_id'] ?? null,
                'event_type' => $eventType->value,
                'category' => $eventType->category()->value,
                'priority' => $priority->value,
                'status' => NotificationStatus::Created->value,
                'title' => $data['title'],
                'body' => $data['body'],
                'icon' => $data['icon'] ?? $eventType->category()->icon(),
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'action_required' => $data['action_required'] ?? $eventType->actionRequired(),
                'action_label' => $data['action_label'] ?? $eventType->defaultActionLabel(),
                'group_key' => $groupKey,
                'occurrence_count' => 1,
                'last_occurred_at' => now(),
                'payload' => $this->buildPayload($data, $reference),
                'created_by' => $data['created_by'] ?? null,
            ]);

            $this->fanOut($notification, $recipients, $eventType);

            return $notification->fresh();
        });
    }

    /**
     * If the same underlying problem (same group_key) already produced a
     * notification within the cooldown window, bump its occurrence counter
     * instead of spamming a new row — and re-surface it for anyone who
     * already dismissed/read the earlier occurrence, since a recurring
     * problem should keep bothering people until it's actually resolved.
     */
    private function reopenGroupedNotification(Shop $shop, string $groupKey, int $cooldownMinutes): ?Notification
    {
        $existing = Notification::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('group_key', $groupKey)
            ->where('last_occurred_at', '>=', now()->subMinutes($cooldownMinutes))
            ->latest('id')
            ->first();

        if (! $existing) {
            return null;
        }

        $existing->update([
            'occurrence_count' => $existing->occurrence_count + 1,
            'last_occurred_at' => now(),
        ]);

        NotificationRecipient::where('notification_id', $existing->id)
            ->update(['dismissed_at' => null, 'read_at' => null]);

        return $existing->fresh();
    }

    private function fanOut(Notification $notification, Collection $recipients, NotificationEventType $eventType): void
    {
        $anyQueued = false;

        foreach ($recipients as $user) {
            $recipient = NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'shop_id' => $notification->shop_id,
                'delivered_at' => now(),
            ]);

            foreach ($this->effectiveChannels($user, $notification, $eventType) as $channel) {
                $delivery = NotificationDelivery::create([
                    'notification_recipient_id' => $recipient->id,
                    'channel' => $channel->value,
                    'status' => NotificationDeliveryStatus::Pending->value,
                ]);

                if (! $channel->requiresTransport()) {
                    app(NotificationChannelManager::class)->handlerFor($channel)->send($delivery);
                    continue;
                }

                if (! $channel->isImplemented()) {
                    $delivery->update([
                        'status' => NotificationDeliveryStatus::Skipped->value,
                        'error_message' => "{$channel->label()} channel is not implemented yet.",
                    ]);
                    continue;
                }

                $anyQueued = true;
                SendNotificationChannelJob::dispatch($delivery->id);
            }
        }

        $notification->update([
            'status' => ($anyQueued ? NotificationStatus::Queued : NotificationStatus::Delivered)->value,
        ]);
    }

    /**
     * Effective channel set for one recipient = the event type's defaults,
     * WIDENED by any channel the user explicitly turned on for this category
     * and NARROWED by any channel they explicitly turned off. A stored
     * NotificationPreference row only ever exists for an explicit override —
     * its absence means "use the event type's default" (see
     * notification_preferences migration).
     *
     * In-App is never opt-outable — it is the floor every ERP in this class
     * (Dynamics, Odoo) guarantees; the bell/history must always work.
     *
     * @return array<int, NotificationChannel>
     */
    private function effectiveChannels(User $user, Notification $notification, NotificationEventType $eventType): array
    {
        $preferences = NotificationPreference::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('category', $notification->category->value)
            ->get()
            ->keyBy(fn (NotificationPreference $p) => $p->channel->value);

        $defaults = collect($eventType->defaultChannels())->map(fn (NotificationChannel $c) => $c->value);

        return collect(NotificationChannel::cases())
            ->filter(function (NotificationChannel $channel) use ($preferences, $defaults) {
                if ($channel === NotificationChannel::InApp) {
                    return true;
                }

                $pref = $preferences->get($channel->value);

                return $pref !== null ? $pref->is_enabled : $defaults->contains($channel->value);
            })
            ->values()
            ->all();
    }

    private function buildPayload(array $data, ?Model $reference): array
    {
        return array_merge(
            ['deep_link' => $this->deepLinks->resolve($reference)],
            $data['payload'] ?? []
        );
    }
}