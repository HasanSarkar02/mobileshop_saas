<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Enums\ServiceTicketStatus;
use App\Models\Shop;
use App\Models\ServiceTicket;
use App\Services\Notifications\NotificationBatcher;
use App\Services\Notifications\RecipientResolver;

class ServiceFollowUpReminderChecker implements ReminderCheckerInterface
{
    private const READY_STALE_AFTER_DAYS = 3;

    public function __construct(
        private readonly NotificationBatcher $batcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        $tickets = ServiceTicket::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('status', ServiceTicketStatus::Ready->value)
            ->where('ready_at', '<=', now()->subDays(self::READY_STALE_AFTER_DAYS))
            ->limit(100)
            ->get();

        if ($tickets->isEmpty()) {
            return;
        }

        $lines = $tickets->map(fn (ServiceTicket $t) =>
            "{$t->ticket_number} — {$t->customer_name} ({$t->device_model}) ready since " . $t->ready_at->format('d M')
        )->all();

        $this->batcher->dispatchDigest(
            NotificationEventType::ServiceTicketOverdue,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::ServiceView->value),
            'Service tickets awaiting pickup',
            $lines,
            groupKey: "service_followup_digest:{$shop->id}",
            groupCooldownMinutes: 1440,
        );
    }
}