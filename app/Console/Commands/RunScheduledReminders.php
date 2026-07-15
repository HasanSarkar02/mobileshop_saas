<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\Notifications\ReminderCheckers\CustomerDueReminderChecker;
use App\Services\Notifications\ReminderCheckers\FinancePartnerReceivableOverdueChecker;
use App\Services\Notifications\ReminderCheckers\LoanRepaymentReminderChecker;
use App\Services\Notifications\ReminderCheckers\PayrollReminderChecker;
use App\Services\Notifications\ReminderCheckers\ServiceFollowUpReminderChecker;
use App\Services\Notifications\ReminderCheckers\SupplierPaymentReminderChecker;
use App\Services\Notifications\ReminderCheckers\WarrantyExpiryReminderChecker;
use Illuminate\Console\Command;
use Throwable;

class RunScheduledReminders extends Command
{
    protected $signature = 'notifications:run-scheduled-reminders';
    protected $description = 'Runs every registered ReminderCheckerInterface against every active shop. Add a new reminder type by registering a new checker class here — nothing else changes.';

    public function handle(): void
    {
        $checkers = [
            app(CustomerDueReminderChecker::class),
            app(SupplierPaymentReminderChecker::class),
            app(WarrantyExpiryReminderChecker::class),
            app(ServiceFollowUpReminderChecker::class),
            app(PayrollReminderChecker::class),
            app(LoanRepaymentReminderChecker::class),
            app(FinancePartnerReceivableOverdueChecker::class),
        ];

        $shops = Shop::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereIn('status', ['trial', 'active'])
            ->get();

        foreach ($shops as $shop) {
            foreach ($checkers as $checker) {
                try {
                    $checker->check($shop);
                } catch (Throwable $e) {
                    $this->error("Reminder checker failed for shop {$shop->id}: " . get_class($checker) . ' — ' . $e->getMessage());
                }
            }
        }

        $this->info('Scheduled reminders processed for ' . $shops->count() . ' shop(s).');
    }
}