<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionDueReminder;
use App\Models\ShopSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionDueReminders extends Command
{
    protected $signature   = 'billing:send-due-reminders';
    protected $description = 'Send subscription due reminders to shops with payments due in 3 days.';

    public function handle(): void
    {
        $dueIn3Days = ShopSubscription::withoutGlobalScopes()
            ->with(['shop', 'plan'])
            ->whereIn('status', ['active', 'trial'])
            ->whereDate('next_billing_date', now()->addDays(3)->toDateString())
            ->get();

        $sent = 0;

        foreach ($dueIn3Days as $sub) {
            if (! $sub->shop?->email) continue;

            try {
                Mail::to($sub->shop->email)->send(new SubscriptionDueReminder((object) [
                    'name'              => $sub->shop->name,
                    'price_at_signup'   => $sub->price_at_signup,
                    'next_billing_date' => $sub->next_billing_date?->format('d M Y'),
                    'plan_name'         => $sub->plan?->name,
                ]));
                $sent++;
            } catch (\Exception $e) {
                $this->error("Failed to send to {$sub->shop->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} due reminder(s).");
    }
}