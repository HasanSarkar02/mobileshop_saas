<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Models\Purchase;
use App\Models\Shop;
use App\Services\Notifications\NotificationBatcher;
use App\Services\Notifications\RecipientResolver;

class SupplierPaymentReminderChecker implements ReminderCheckerInterface
{
    private const DUE_AFTER_DAYS = 30;

    public function __construct(
        private readonly NotificationBatcher $batcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        $purchases = Purchase::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('purchase_date', '<=', now()->subDays(self::DUE_AFTER_DAYS))
            ->with('supplier')
            ->orderBy('purchase_date')
            ->limit(100)
            ->get();

        if ($purchases->isEmpty()) {
            return;
        }

        $lines = $purchases->map(fn (Purchase $p) =>
            "{$p->supplier?->name}: {$p->reference_number} — ৳" .
            number_format((float) $p->total_amount - (float) $p->amount_paid, 2) .
            ' (' . $p->purchase_date->diffInDays(now()) . ' days old)'
        )->all();

        $this->batcher->dispatchDigest(
            NotificationEventType::SupplierPaymentDue,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::PurchasesApprove->value),
            'Supplier payments due',
            $lines,
            groupKey: "supplier_payment_digest:{$shop->id}",
            groupCooldownMinutes: 4320,
        );
    }
}