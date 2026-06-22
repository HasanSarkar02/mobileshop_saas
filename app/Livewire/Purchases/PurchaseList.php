<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Purchases')]
class PurchaseList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $purchases = Purchase::with('supplier', 'branch', 'createdBy')
            ->when($this->search, fn($q) =>
                $q->where('reference_number', 'like', "%{$this->search}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$this->search}%"))
            )
            ->when($this->dateFrom, fn($q) => $q->where('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('purchase_date', '<=', $this->dateTo))
            ->latest('purchase_date')
            ->paginate(20);

        return view('livewire.purchases.purchase-list', compact('purchases'));
    }
}