<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Shop;
use App\Models\ShopSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
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

    #[Url]
    public string $planFilter   = '';

    // Assign Plan Modal
    public bool   $showAssignModal = false;
    public ?int   $assignShopId    = null;
    public int    $assignPlanId    = 0;
    public string $assignCycle     = 'monthly';
    public string $assignTrialDays = '14';
    public string $assignNotes     = '';

    // Manual Invoice Modal
    public bool   $showInvoiceModal = false;
    public ?int   $invoiceSubId     = null;
    public string $invoiceAmount    = '';
    public string $invoiceDueDate   = '';
    public string $invoiceNotes     = '';

    #[Computed]
    public function stats(): object
    {
        $subs = DB::table('shop_subscriptions');

        return (object) [
            'total_shops'   => Shop::withoutGlobalScopes()->count(),
            'active'        => (clone $subs)->where('status', 'active')->count(),
            'trial'         => (clone $subs)->where('status', 'trial')->count(),
            'past_due'      => (clone $subs)->where('status', 'past_due')->count(),
            'suspended'     => (clone $subs)->where('status', 'suspended')->count(),
            'no_sub'        => Shop::withoutGlobalScopes()
                                    ->whereDoesntHave('subscription')
                                    ->count(),
            'mrr'           => (float) DB::table('shop_subscriptions')
                                ->where('status', 'active')
                                ->where('billing_cycle', 'monthly')
                                ->sum('price_at_signup'),
            'due_this_week' => (clone $subs)
                                ->whereIn('status', ['active', 'past_due'])
                                ->whereBetween('next_billing_date', [
                                    now()->toDateString(),
                                    now()->addDays(7)->toDateString(),
                                ])->count(),
        ];
    }

    #[Computed]
    public function subscriptions()
    {
        return ShopSubscription::withoutGlobalScopes()
            ->with(['shop', 'plan'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->planFilter,   fn ($q) => $q->where('plan_id', $this->planFilter))
            ->when($this->search,       fn ($q) =>
                $q->whereHas('shop', fn ($sq) =>
                    $sq->where('name', 'like', "%{$this->search}%")
                       ->orWhere('email', 'like', "%{$this->search}%")
                )
            )
            ->orderBy(DB::raw("FIELD(status, 'past_due', 'trial', 'active', 'suspended', 'cancelled')"))
            ->orderBy('next_billing_date')
            ->paginate(25);
    }

    #[Computed]
    public function plans(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function shopsWithoutSub(): \Illuminate\Database\Eloquent\Collection
    {
        return Shop::withoutGlobalScopes()
            ->whereDoesntHave('subscription')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── Actions ──────────────────────────────────────────────────────────────

    public function openAssignModal(?int $shopId = null): void
    {
        $this->assignShopId    = $shopId;
        $this->assignPlanId    = 0;
        $this->assignCycle     = 'monthly';
        $this->assignTrialDays = '14';
        $this->assignNotes     = '';
        $this->showAssignModal = true;
    }

    public function assignPlan(): void
    {
        $this->validate([
            'assignShopId' => 'required|integer',
            'assignPlanId' => 'required|integer|min:1',
            'assignCycle'  => 'required|in:monthly,yearly',
        ], ['assignPlanId.min' => 'Please select a plan.']);

        $plan = SubscriptionPlan::findOrFail($this->assignPlanId);
        $shop = Shop::withoutGlobalScopes()->findOrFail($this->assignShopId);

        $trialDays  = max(0, (int) $this->assignTrialDays);
        $trialEnds  = $trialDays > 0 ? now()->addDays($trialDays)->toDateString() : null;
        $periodEnd  = $trialDays > 0
            ? now()->addDays($trialDays)->toDateString()
            : now()->addMonth()->toDateString();

        $price = $this->assignCycle === 'yearly'
            ? $plan->yearly_price ?? $plan->monthly_price * 12
            : $plan->monthly_price;

        // Deactivate existing subscription
        ShopSubscription::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereNotIn('status', ['cancelled'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        ShopSubscription::create([
            'shop_id'              => $shop->id,
            'plan_id'              => $plan->id,
            'billing_cycle'        => $this->assignCycle,
            'price_at_signup'      => $price,
            'status'               => $trialDays > 0 ? 'trial' : 'active',
            'trial_ends_at'        => $trialEnds,
            'current_period_start' => now()->toDateString(),
            'current_period_end'   => $periodEnd,
            'next_billing_date'    => $periodEnd,
            'notes'                => $this->assignNotes ?: null,
        ]);

        // Update shop status
        Shop::withoutGlobalScopes()->where('id', $shop->id)
            ->update(['status' => 'active', 'is_active' => true]);

        $this->showAssignModal = false;
        unset($this->subscriptions, $this->stats, $this->shopsWithoutSub);

        $this->dispatch('notify', ['type' => 'success',
            'message' => "{$plan->name} assigned to {$shop->name}."]);
    }

    public function extendTrial(int $subId, int $days = 7): void
    {
        $sub = ShopSubscription::withoutGlobalScopes()->findOrFail($subId);

        $newEnd = \Carbon\Carbon::parse($sub->trial_ends_at ?? now())->addDays($days);

        $sub->update([
            'trial_ends_at'      => $newEnd->toDateString(),
            'current_period_end' => $newEnd->toDateString(),
            'next_billing_date'  => $newEnd->toDateString(),
        ]);

        unset($this->subscriptions);
        $this->dispatch('notify', ['type' => 'success',
            'message' => "Trial extended by {$days} days until {$newEnd->format('d M Y')}."]);
    }

    public function markPaid(int $subId): void
    {
        $sub = ShopSubscription::withoutGlobalScopes()->findOrFail($subId);

        $nextDate = $sub->billing_cycle === 'yearly'
            ? now()->addYear()->toDateString()
            : now()->addMonth()->toDateString();

        $sub->update([
            'status'               => 'active',
            'current_period_start' => now()->toDateString(),
            'current_period_end'   => $nextDate,
            'next_billing_date'    => $nextDate,
        ]);

        // Create paid invoice record
        $invoiceNumber = 'INV-' . now()->format('Y') . '-' . str_pad(
            SubscriptionInvoice::max('id') + 1, 5, '0', STR_PAD_LEFT
        );

        SubscriptionInvoice::create([
            'shop_id'          => $sub->shop_id,
            'subscription_id'  => $sub->id,
            'invoice_number'   => $invoiceNumber,
            'amount'           => $sub->price_at_signup,
            'status'           => 'paid',
            'due_date'         => now()->toDateString(),
            'paid_at'          => now()->toDateString(),
            'payment_method'   => 'manual',
        ]);

        // Activate shop
        Shop::withoutGlobalScopes()->where('id', $sub->shop_id)
            ->update(['status' => 'active', 'is_active' => true]);

        unset($this->subscriptions, $this->stats);
        $this->dispatch('notify', ['type' => 'success',
            'message' => 'Payment recorded. Subscription activated until ' . $nextDate . '.']);
    }

    public bool $showSuspendModal = false;
    public ?int $suspendSubId = null;
    public string $suspendReason = '';

    public function openSuspendModal(int $subId): void
    {
        $this->suspendSubId = $subId;
        $this->suspendReason = '';
        $this->showSuspendModal = true;
    }

    public function confirmSuspend(\App\Services\AdminAuditLogger $audit): void
    {
        $this->validate(['suspendReason' => 'required|string|min:3|max:500']);

        $sub = ShopSubscription::withoutGlobalScopes()->findOrFail($this->suspendSubId);
        $sub->update(['status' => 'suspended']);

        $shop = Shop::withoutGlobalScopes()->where('id', $sub->shop_id)->first();
        Shop::withoutGlobalScopes()->where('id', $sub->shop_id)->update([
            'status' => 'suspended',
            'is_active' => false,
            'suspension_reason' => $this->suspendReason,
            'suspended_at' => now(),
        ]);

        $audit->log(\Illuminate\Support\Facades\Auth::guard('admin')->user(), 'shop.suspended', $shop, $this->suspendReason, ['subscription_id' => $sub->id]);

        unset($this->subscriptions, $this->stats);
        $this->showSuspendModal = false;
        $this->dispatch('notify', ['type' => 'warning', 'message' => 'Shop suspended.']);
    }

    public function reactivate(int $subId): void
    {
        $sub = ShopSubscription::withoutGlobalScopes()->findOrFail($subId);
        $sub->update(['status' => 'active']);

        Shop::withoutGlobalScopes()->where('id', $sub->shop_id)
            ->update(['status' => 'active', 'is_active' => true]);

        unset($this->subscriptions, $this->stats);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Shop reactivated.']);
    }

    public function markPastDue(int $subId): void
    {
        $sub = ShopSubscription::withoutGlobalScopes()->findOrFail($subId);
        $sub->update(['status' => 'past_due']);

        unset($this->subscriptions, $this->stats);
        $this->dispatch('notify', ['type' => 'warning', 'message' => 'Marked as past due.']);
    }

    public function sendDueReminder(int $subId): void
    {
        $sub = ShopSubscription::withoutGlobalScopes()
            ->with(['shop', 'plan'])
            ->findOrFail($subId);

        if (! $sub->shop?->email) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'Shop has no email address on file.']);
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($sub->shop->email)
                ->send(new \App\Mail\SubscriptionDueReminder((object) [
                    'name'              => $sub->shop->name,
                    'price_at_signup'   => $sub->price_at_signup,
                    'next_billing_date' => $sub->next_billing_date?->format('d M Y'),
                    'plan_name'         => $sub->plan?->name,
                ]));

            $this->dispatch('notify', ['type' => 'success',
                'message' => "Reminder sent to {$sub->shop->email}"]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage()]);
        }
    }

    public function openInvoiceModal(int $subId): void
    {
        $sub = ShopSubscription::withoutGlobalScopes()->findOrFail($subId);
        $this->invoiceSubId   = $subId;
        $this->invoiceAmount  = (string) $sub->price_at_signup;
        $this->invoiceDueDate = now()->addDays(7)->format('Y-m-d');
        $this->invoiceNotes   = '';
        $this->showInvoiceModal = true;
    }

    public function createInvoice(): void
    {
        $this->validate([
            'invoiceAmount'  => 'required|numeric|min:1',
            'invoiceDueDate' => 'required|date',
        ]);

        $sub = ShopSubscription::withoutGlobalScopes()->findOrFail($this->invoiceSubId);

        $invoiceNumber = 'INV-' . now()->format('Y') . '-' . str_pad(
            (SubscriptionInvoice::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT
        );

        SubscriptionInvoice::create([
            'shop_id'         => $sub->shop_id,
            'subscription_id' => $sub->id,
            'invoice_number'  => $invoiceNumber,
            'amount'          => (float) $this->invoiceAmount,
            'status'          => 'pending',
            'due_date'        => $this->invoiceDueDate,
            'notes'           => $this->invoiceNotes ?: null,
        ]);

        // Update subscription to past_due if not already active paid
        if ($sub->status !== 'active') {
            $sub->update(['status' => 'past_due', 'next_billing_date' => $this->invoiceDueDate]);
        }

        $this->showInvoiceModal = false;
        $this->dispatch('notify', ['type' => 'success',
            'message' => "Invoice {$invoiceNumber} created."]);
    }

    public function render()
    {
        return view('livewire.admin.billing-dashboard');
    }
}