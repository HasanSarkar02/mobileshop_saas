<?php

namespace App\Livewire\Sales;

use App\Actions\VoidSaleAction;
use App\Enums\SaleStatus;
use App\Models\Sale;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Sales History')]
class SaleList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'confirmed';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public bool   $showVoidModal = false;
    public ?int   $voidSaleId    = null;
    public string $voidReason    = '';

    public function updatingSearch(): void { $this->resetPage(); }

    #[Computed]
    public function todaySummary(): array
    {
        $today = Sale::where('status', SaleStatus::Confirmed)
            ->whereDate('confirmed_at', today())
            ->selectRaw('COUNT(*) as count, SUM(grand_total) as revenue, SUM(gross_profit) as profit')
            ->first();

        return [
            'count'   => $today->count ?? 0,
            'revenue' => $today->revenue ?? 0,
            'profit'  => $today->profit ?? 0,
        ];
    }

    public function openVoidModal(int $saleId): void
    {
        $this->voidSaleId = $saleId;
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    public function voidSale(VoidSaleAction $action): void
    {
        $this->validate([
            'voidReason' => 'required|string|min:5',
        ], [
            'voidReason.min' => 'Please provide a reason (min 5 characters).',
        ]);

        $sale = Sale::findOrFail($this->voidSaleId);

        try {
            $action->execute($sale, $this->voidReason, auth()->user());
            $this->showVoidModal = false;
            $this->dispatch('notify', type: 'warning', message: "Sale {$sale->sale_number} voided.");
            unset($this->todaySummary);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $sales = Sale::with(['customer', 'branch', 'cashier'])
            ->when($this->search, fn ($q) =>
                $q->where('sale_number', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$this->search}%"))
            )
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('confirmed_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('confirmed_at', '<=', $this->dateTo))
            ->latest('confirmed_at')
            ->paginate(25);

        return view('livewire.sales.sale-list', compact('sales'));
    }
}