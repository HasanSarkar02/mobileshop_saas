<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Models\PayrollRun;
use App\Models\Shop;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\RecipientResolver;

class PayrollReminderChecker implements ReminderCheckerInterface
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        // Only in the final week of the month; group_key + long cooldown
        // keeps it to effectively once per month regardless of run frequency.
        if ((int) now()->format('j') < now()->daysInMonth - 6) {
            return;
        }

        $exists = PayrollRun::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->exists();

        if ($exists) {
            return;
        }

        $this->dispatcher->dispatch(
            NotificationEventType::PayrollReminderDue,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::PayrollManage->value),
            [
                'title' => 'Payroll not yet started for ' . now()->format('F Y'),
                'body' => 'No payroll draft exists for this month. Generate one from the Payroll module.',
                'group_key' => "payroll_reminder:{$shop->id}:" . now()->format('Y-m'),
                'group_cooldown_minutes' => 40320,
            ]
        );
    }
}