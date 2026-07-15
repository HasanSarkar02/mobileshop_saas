<?php

namespace App\Services\Notifications;

use App\Enums\NotificationEventType;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationBatcher
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<int, string>  $itemDescriptions
     */
    public function dispatchDigest(
        NotificationEventType $eventType,
        Shop $shop,
        Collection $recipients,
        string $title,
        array $itemDescriptions,
        ?string $groupKey = null,
        int $groupCooldownMinutes = 1440,
    ): void {
        if (empty($itemDescriptions)) {
            return;
        }

        $count = count($itemDescriptions);
        $preview = implode("\n", array_slice($itemDescriptions, 0, 5));
        $more = $count > 5 ? "\n…and " . ($count - 5) . ' more.' : '';

        $this->dispatcher->dispatch($eventType, $shop, $recipients, [
            'title' => $title . " ({$count})",
            'body' => $preview . $more,
            'group_key' => $groupKey ?? ($eventType->value . ':' . $shop->id),
            'group_cooldown_minutes' => $groupCooldownMinutes,
            'payload' => ['digest_count' => $count],
        ]);
    }
}