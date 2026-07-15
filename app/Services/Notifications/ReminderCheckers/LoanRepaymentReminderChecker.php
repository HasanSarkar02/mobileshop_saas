<?php

namespace App\Services\Notifications\ReminderCheckers;

use App\Enums\NotificationEventType;
use App\Models\Account;
use App\Models\Shop;
use App\Models\TreasuryTransaction;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\RecipientResolver;

/**
 * No loan due-date field exists anywhere in this schema — this is a
 * best-effort heuristic (outstanding GL 2100 balance + no repayment posted
 * in 30 days), not a precise due-date reminder. A real one needs a
 * due_date column on TreasuryTransaction, out of scope here.
 */
class LoanRepaymentReminderChecker implements ReminderCheckerInterface
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
        private readonly RecipientResolver $recipients,
    ) {}

    public function check(Shop $shop): void
    {
        $loanAccount = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('code', '2100')
            ->first();

        if (! $loanAccount || $loanAccount->balance() <= 0) {
            return;
        }

        $recentRepayment = TreasuryTransaction::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('transaction_type', 'loan_repayment')
            ->where('status', 'completed')
            ->where('transaction_date', '>=', now()->subDays(30))
            ->exists();

        if ($recentRepayment) {
            return;
        }

        $this->dispatcher->dispatch(
            NotificationEventType::LoanRepaymentDue,
            $shop,
            $this->recipients->owner($shop),
            [
                'title' => 'Outstanding loan balance — no recent repayment',
                'body' => 'Short-term loans payable: ৳' . number_format($loanAccount->balance(), 2) .
                    '. No repayment recorded in the last 30 days.',
                'group_key' => "loan_repayment_reminder:{$shop->id}",
                'group_cooldown_minutes' => 10080,
            ]
        );
    }
}