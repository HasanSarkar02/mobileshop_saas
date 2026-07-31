<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\FollowUpStatus;
use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Models\CustomerDueFollowUp;
use App\Models\Shop;
use App\Services\Notifications\NotificationBatcher;
use App\Services\Notifications\RecipientResolver;

class FollowUpReminderChecker implements ReminderCheckerInterface
{
    public function __construct(
        private readonly NotificationBatcher $batcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        $this->checkDueToday($shop);
        $this->checkBrokenPromises($shop);
    }

    private function checkDueToday(Shop $shop): void
    {
        $followUps = CustomerDueFollowUp::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereDate('next_followup_date', now()->toDateString())
            ->whereNull('completed_at')
            ->whereNotIn('status', [FollowUpStatus::Paid->value, FollowUpStatus::Cancelled->value])
            ->with(['customer' => fn ($q) => $q->withoutGlobalScopes()])
            ->limit(100)
            ->get();

        if ($followUps->isEmpty()) {
            return;
        }

        $lines = $followUps->map(fn (CustomerDueFollowUp $f) =>
            "{$f->customer?->name}: ৳" . number_format((float) $f->customer?->current_balance, 2)
        )->all();

        $this->batcher->dispatchDigest(
            NotificationEventType::CollectionFollowUpDue,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::CustomersManageFollowups->value),
            'Follow-ups due today',
            $lines,
            groupKey: "followup_due_today:{$shop->id}:" . now()->format('Y-m-d'),
            groupCooldownMinutes: 1440,
        );
    }

    private function checkBrokenPromises(Shop $shop): void
    {
        $followUps = CustomerDueFollowUp::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereDate('promised_payment_date', '<', now()->toDateString())
            ->whereNull('completed_at')
            ->whereNotIn('status', [FollowUpStatus::Paid->value, FollowUpStatus::Cancelled->value])
            ->with(['customer' => fn ($q) => $q->withoutGlobalScopes()])
            ->limit(100)
            ->get()
            ->filter(fn (CustomerDueFollowUp $f) => $f->customer && (float) $f->customer->current_balance > 0);

        if ($followUps->isEmpty()) {
            return;
        }

        $lines = $followUps->map(fn (CustomerDueFollowUp $f) =>
            "{$f->customer?->name} — promised {$f->promised_payment_date->format('d M Y')}, still owes ৳" .
            number_format((float) $f->customer?->current_balance, 2)
        )->all();

        $this->batcher->dispatchDigest(
            NotificationEventType::CollectionPromiseBroken,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::CustomersManageFollowups->value),
            'Broken payment promises',
            $lines,
            groupKey: "followup_broken_promise:{$shop->id}:" . now()->format('Y-m-d'),
            groupCooldownMinutes: 1440,
        );
    }
}