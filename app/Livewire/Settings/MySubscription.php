<?php

namespace App\Livewire\Settings;

use App\Models\ShopSubscription;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('My Subscription')]
class MySubscription extends Component
{
    use \App\Traits\HasAuthorization;

    public function mount(): void
    {
        // Only owner can see billing
        if (! Auth::user()->isOwner()) {
            abort(403);
        }
    }

    #[Computed]
    public function subscription(): ?ShopSubscription
    {
        return ShopSubscription::where('shop_id', Auth::user()->shop_id)
            ->with('plan')
            ->latest()
            ->first();
    }

    #[Computed]
    public function invoices(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionInvoice::where('shop_id', Auth::user()->shop_id)
            ->orderByDesc('due_date')
            ->limit(12)
            ->get();
    }

    #[Computed]
    public function shopStats(): object
    {
        $shopId = Auth::user()->shop_id;
        return (object) [
            'branches'  => \App\Models\Branch::where('shop_id', $shopId)->count(),
            'employees' => \App\Models\User::where('shop_id', $shopId)->where('user_type', 'employee')->count(),
            'products'  => \App\Models\Product::where('shop_id', $shopId)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.settings.my-subscription');
    }
}