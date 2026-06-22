<?php

namespace App\Livewire\Admin;

use App\Enums\ShopStatus;
use App\Models\Shop;
use App\Services\ImpersonationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Shop Detail')]
class ShopDetail extends Component
{
    public int $shopId;

    public function mount(Shop $shop): void
    {
        $this->shopId = $shop->id;
    }

    public function activate(): void
    {
        Shop::withoutGlobalScopes()->findOrFail($this->shopId)
            ->update(['status' => ShopStatus::Active, 'is_active' => true]);
        $this->dispatch('notify', type: 'success', message: 'Shop activated.');
    }

    public function suspend(): void
    {
        Shop::withoutGlobalScopes()->findOrFail($this->shopId)
            ->update(['status' => ShopStatus::Suspended, 'is_active' => false]);
        $this->dispatch('notify', type: 'warning', message: 'Shop suspended.');
    }

    public function impersonate(ImpersonationService $service): void
    {
        $shop = Shop::withoutGlobalScopes()->with('owner')->findOrFail($this->shopId);

        if (! $shop->owner) {
            $this->dispatch('notify', type: 'error', message: 'No owner found for this shop.');
            return;
        }

        $service->start(request(), Auth::guard('admin')->user(), $shop->owner, 'Admin initiated via shop detail page.');

        $this->redirect('/dashboard', navigate: true);
    }

    public function render()
    {
        $shop = Shop::withoutGlobalScopes()
            ->with('owner', 'branches', 'subscriptionPlan')
            ->findOrFail($this->shopId);

        return view('livewire.admin.shop-detail', compact('shop'));
    }
}