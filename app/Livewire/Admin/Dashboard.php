<?php

namespace App\Livewire\Admin;

use App\Enums\ShopStatus;
use App\Models\Shop;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'stats' => [
                'total' => Shop::withoutGlobalScopes()->count(),
                'active' => Shop::withoutGlobalScopes()->where('status', ShopStatus::Active)->count(),
                'trial' => Shop::withoutGlobalScopes()->where('status', ShopStatus::Trial)->count(),
                'suspended' => Shop::withoutGlobalScopes()->where('status', ShopStatus::Suspended)->count(),
                'expired' => Shop::withoutGlobalScopes()->where('status', ShopStatus::Expired)->count(),
            ],
            'recentShops' => Shop::withoutGlobalScopes()
                ->with('owner')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}