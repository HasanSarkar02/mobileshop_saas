<?php

namespace App\Livewire\SuperAdmin;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Billing & Subscriptions')]
class BillingDashboard extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $search       = '';

    #[Computed]
    public function stats(): object
    {
        return (object) [
            'total_shops'   => DB::table('shops')->count(),
            'active_subs'   => DB::table('shop_subscriptions')->where('status', 'active')->count(),
            'trial_subs'    => DB::table('shop_subscriptions')->where('status', 'trial')->count(),
            'past_due'      => DB::table('shop_subscriptions')->where('status', 'past_due')->count(),
            'mrr'           => (float) DB::table('shop_subscriptions')
                                ->where('status', 'active')
                                ->where('billing_cycle', 'monthly')
                                ->join('subscription_plans','subscription_plans.id','=','shop_subscriptions.plan_id')
                                ->sum('subscription_plans.monthly_price'),
            'due_this_week' => DB::table('shop_subscriptions')
                                ->where('status', 'active')
                                ->whereBetween('next_billing_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                                ->count(),
        ];
    }

    #[Computed]
    public function subscriptions()
    {
        return DB::table('shop_subscriptions')
            ->join('shops',              'shops.id',              '=', 'shop_subscriptions.shop_id')
            ->join('subscription_plans', 'subscription_plans.id','=', 'shop_subscriptions.plan_id')
            ->when($this->statusFilter, fn ($q) => $q->where('shop_subscriptions.status', $this->statusFilter))
            ->when($this->search,       fn ($q) => $q->where('shops.name', 'like', "%{$this->search}%"))
            ->select([
                'shop_subscriptions.*',
                'shops.name         AS shop_name',
                'shops.phone        AS shop_phone',
                'subscription_plans.name AS plan_name',
                'subscription_plans.monthly_price',
            ])
            ->orderBy('shop_subscriptions.next_billing_date')
            ->paginate(20);
    }

    public function extendTrial(int $subId, int $days = 7): void
    {
        $sub = DB::table('shop_subscriptions')->where('id', $subId)->first();
        if (! $sub) return;

        $newEnd = \Carbon\Carbon::parse($sub->trial_ends_at ?? now())->addDays($days);

        DB::table('shop_subscriptions')->where('id', $subId)->update([
            'trial_ends_at'      => $newEnd->toDateString(),
            'current_period_end' => $newEnd->toDateString(),
            'updated_at'         => now(),
        ]);

        unset($this->subscriptions);
        $this->dispatch('notify', ['type' => 'success',
            'message' => "Trial extended by {$days} days."]);
    }

    public function markPaid(int $subId): void
    {
        DB::table('shop_subscriptions')->where('id', $subId)->update([
            'status'             => 'active',
            'current_period_start'=> now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
            'next_billing_date'  => now()->addMonth()->toDateString(),
            'updated_at'         => now(),
        ]);

        unset($this->subscriptions);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Marked as paid. Subscription active.']);
    }

    public function suspend(int $subId): void
    {
        DB::table('shop_subscriptions')->where('id', $subId)
            ->update(['status' => 'suspended', 'updated_at' => now()]);

        unset($this->subscriptions);
        $this->dispatch('notify', ['type' => 'warning', 'message' => 'Shop subscription suspended.']);
    }

    public function sendDueReminder(int $subId): void
    {
        $sub = DB::table('shop_subscriptions')
            ->join('shops', 'shops.id', '=', 'shop_subscriptions.shop_id')
            ->where('shop_subscriptions.id', $subId)
            ->select('shops.email', 'shops.name', 'shop_subscriptions.price_at_signup', 'shop_subscriptions.next_billing_date')
            ->first();

        if (! $sub?->email) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Shop has no email address.']);
            return;
        }

        // Send via Laravel Mail
        \Illuminate\Support\Facades\Mail::to($sub->email)
            ->queue(new \App\Mail\SubscriptionDueReminder($sub));

        $this->dispatch('notify', ['type' => 'success',
            'message' => "Due reminder sent to {$sub->email}"]);
    }

    public function render()
    {
        return view('livewire.admin.billing-dashboard', [
            'stats'         => $this->stats,
            'subscriptions' => $this->subscriptions,
        ]);
    }
}