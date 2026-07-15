<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Models\Customer;
use App\Models\Shop;
use App\Services\Notifications\NotificationBatcher;
use App\Services\Notifications\RecipientResolver;

class CustomerDueReminderChecker implements ReminderCheckerInterface
{
    public function __construct(
        private readonly NotificationBatcher $batcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        $customers = Customer::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('current_balance', '>', 0)
            ->where('is_active', true)
            ->orderByDesc('current_balance')
            ->limit(100)
            ->get();

        if ($customers->isEmpty()) {
            return;
        }

        $lines = $customers->map(fn (Customer $c) =>
            "{$c->name}: ৳" . number_format((float) $c->current_balance, 2)
        )->all();

        $this->batcher->dispatchDigest(
            NotificationEventType::CustomerDueReminder,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::CustomersRecordDuePayment->value),
            'Customers with outstanding dues',
            $lines,
            groupKey: "customer_due_digest:{$shop->id}",
            groupCooldownMinutes: 4320,
        );
    }
}