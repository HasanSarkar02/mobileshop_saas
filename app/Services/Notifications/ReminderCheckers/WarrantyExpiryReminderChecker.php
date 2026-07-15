<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Enums\UnitStatus;
use App\Models\ProductUnit;
use App\Models\Shop;
use App\Services\Notifications\NotificationBatcher;
use App\Services\Notifications\RecipientResolver;

class WarrantyExpiryReminderChecker implements ReminderCheckerInterface
{
    private const WARN_WITHIN_DAYS = 7;

    public function __construct(
        private readonly NotificationBatcher $batcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        $units = ProductUnit::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('status', UnitStatus::Sold->value)
            ->whereNotNull('sold_at')
            ->where('shop_warranty_days', '>', 0)
            ->with('variant.product')
            ->get()
            ->filter(function (ProductUnit $unit) {
                $expiry = $unit->shopWarrantyExpiresAt();
                return $expiry && $expiry->isFuture() && $expiry->diffInDays(now()) <= self::WARN_WITHIN_DAYS;
            })
            ->take(100);

        if ($units->isEmpty()) {
            return;
        }

        $lines = $units->map(fn (ProductUnit $u) =>
            "{$u->serial_number} ({$u->variant?->product?->name}) — expires " .
            $u->shopWarrantyExpiresAt()->format('d M Y')
        )->all();

        $this->batcher->dispatchDigest(
            NotificationEventType::WarrantyExpiringSoon,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::WarrantyView->value),
            'Warranties expiring soon',
            $lines,
            groupKey: "warranty_expiry_digest:{$shop->id}",
            groupCooldownMinutes: 1440,
        );
    }
}