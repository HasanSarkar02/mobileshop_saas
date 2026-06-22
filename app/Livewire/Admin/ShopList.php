<?php

namespace App\Livewire\Admin;

use App\Enums\ShopStatus;
use App\Models\Shop;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Shops')]
class ShopList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }

    public function suspend(int $shopId): void
    {
        // DB::transaction not needed — single model update
        Shop::withoutGlobalScopes()->findOrFail($shopId)->update([
            'status' => ShopStatus::Suspended,
            'is_active' => false,
        ]);
        $this->dispatch('notify', type: 'warning', message: 'Shop suspended.');
    }

    public function activate(int $shopId): void
    {
        Shop::withoutGlobalScopes()->findOrFail($shopId)->update([
            'status' => ShopStatus::Active,
            'is_active' => true,
        ]);
        $this->dispatch('notify', type: 'success', message: 'Shop activated.');
    }

    public function render()
    {
        $shops = Shop::withoutGlobalScopes()
            ->with('owner')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.shop-list', compact('shops'));
    }
}