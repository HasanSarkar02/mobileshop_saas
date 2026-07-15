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
use App\Models\NotificationRule;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationDispatcher
{
    public function __construct(
        private readonly DeepLinkResolver $deepLinks,
        private readonly NotificationRuleEvaluator $rules,
        private readonly RecipientResolver $recipientResolver,
    ) {}

    /**
     * @param  Collection<int, User>  $recipients  Default audience — may be overridden by a matching NotificationRule.
     * @param  array{
     *   title: string, body: string, reference?: Model|null, branch_id?: int|null,
     *   priority?: \App\Enums\NotificationPriority|null, action_required?: bool|null,
     *   action_label?: string|null, icon?: string|null, group_key?: string|null,
     *   group_cooldown_minutes?: int|null, payload?: array|null, placeholders?: array|null,
     *   created_by?: int|null,
     * }  $data
     * @param  array<string, mixed>  $context  Raw values (e.g. ['amount' => 5000.0]) evaluated against NotificationRule conditions.
     */
    public function dispatch(
        NotificationEventType $eventType,
        Shop $shop,
        Collection $recipients,
        array $data,
        array $context = [],
    ): ?Notification {
        $rule = $this->rules->resolve($shop, $eventType, $context);

        if ($rule) {
            $recipients = $this->applyRecipientOverride($rule, $shop, $recipients);
        }

        if ($recipients->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($eventType, $shop, $recipients, $data, $rule) {
            $groupKey = $data['group_key'] ?? null;
            $cooldownMinutes = $data['group_cooldown_minutes'] ?? 30;

            if ($groupKey) {
                $existing = $this->reopenGroupedNotification($shop, $groupKey, $cooldownMinutes);
                if ($existing) {
                    return $existing;
                }
            }

            $reference = $data['reference'] ?? null;
            $priority = $rule?->priority_override ?? $data['priority'] ?? $eventType->defaultPriority();

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

            $this->fanOut($notification, $recipients, $eventType, $rule);

            return $notification->fresh();
        });
    }

    private function applyRecipientOverride(NotificationRule $rule, Shop $shop, Collection $default): Collection
    {
        return match ($rule->recipient_override_type) {
            'permission' => $rule->recipient_override_permission
                ? $this->recipientResolver->byPermission($shop, $rule->recipient_override_permission)
                : $default,
            'users' => $rule->recipient_override_user_ids
                ? $this->recipientResolver->byUsers(
                    User::withoutGlobalScopes()->whereIn('id', $rule->recipient_override_user_ids)->get()
                )
                : $default,
            default => $default,
        };
    }

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

    private function fanOut(Notification $notification, Collection $recipients, NotificationEventType $eventType, ?NotificationRule $rule): void
    {
        $anyQueued = false;

        foreach ($recipients as $user) {
            $recipient = NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'shop_id' => $notification->shop_id,
                'delivered_at' => now(),
            ]);

            foreach ($this->effectiveChannels($user, $notification, $eventType, $rule) as $channel) {
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
     * Baseline channel set = the rule's channel_override if one applies,
     * otherwise the event type's own defaults. Per-user NotificationPreference
     * rows then still widen/narrow on top of that baseline. In-App is never
     * opt-outable by either a rule or a preference.
     *
     * @return array<int, NotificationChannel>
     */
    private function effectiveChannels(User $user, Notification $notification, NotificationEventType $eventType, ?NotificationRule $rule): array
    {
        $preferences = NotificationPreference::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('category', $notification->category->value)
            ->get()
            ->keyBy(fn (NotificationPreference $p) => $p->channel->value);

        $baseline = $rule?->channel_override
            ?? collect($eventType->defaultChannels())->map(fn (NotificationChannel $c) => $c->value)->all();

        $baseline = collect($baseline);

        return collect(NotificationChannel::cases())
            ->filter(function (NotificationChannel $channel) use ($preferences, $baseline) {
                if ($channel === NotificationChannel::InApp) {
                    return true;
                }

                $pref = $preferences->get($channel->value);

                return $pref !== null ? $pref->is_enabled : $baseline->contains($channel->value);
            })
            ->values()
            ->all();
    }

    private function buildPayload(array $data, ?Model $reference): array
    {
        return array_merge(
            [
                'deep_link' => $this->deepLinks->resolve($reference),
                'placeholders' => $data['placeholders'] ?? [],
            ],
            $data['payload'] ?? []
        );
    }
}