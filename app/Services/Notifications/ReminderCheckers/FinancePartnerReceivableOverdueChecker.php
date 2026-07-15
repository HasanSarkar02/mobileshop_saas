<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Models\FinancePartnerReceivable;
use App\Models\Shop;
use App\Services\Notifications\NotificationBatcher;
use App\Services\Notifications\RecipientResolver;

class FinancePartnerReceivableOverdueChecker implements ReminderCheckerInterface
{
    private const OVERDUE_AFTER_DAYS = 30;

    public function __construct(
        private readonly NotificationBatcher $batcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        $receivables = FinancePartnerReceivable::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereIn('status', ['pending', 'partial'])
            ->where('created_at', '<=', now()->subDays(self::OVERDUE_AFTER_DAYS))
            ->with('financePartner')
            ->limit(100)
            ->get();

        if ($receivables->isEmpty()) {
            return;
        }

        $lines = $receivables->map(fn (FinancePartnerReceivable $r) =>
            "{$r->financePartner?->name}: ৳" . number_format($r->pendingAmount(), 2) . ' (sale #' . $r->sale_id . ')'
        )->all();

        $this->batcher->dispatchDigest(
            NotificationEventType::FpReceivableOverdue,
            $shop,
            $this->recipients->byPermission($shop, PermissionEnum::FinancePartnersViewDue->value),
            'Finance partner receivables overdue',
            $lines,
            groupKey: "fp_overdue_digest:{$shop->id}",
            groupCooldownMinutes: 4320,
        );
    }
}