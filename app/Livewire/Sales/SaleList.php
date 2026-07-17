<?php

namespace App\Livewire\Sales;

use App\Actions\VoidSaleAction;
use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
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
    use \App\Traits\HasAuthorization;
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

    public function mount(): void
    {
        $this->requirePermission('sales.view');
    }

    #[Computed]
    public function todaySummary(): array
    {
        $today = Sale::where('status', SaleStatus::Confirmed)
            ->whereDate('confirmed_at', today())
            ->selectRaw('COUNT(*) as count, SUM(grand_total) as revenue, SUM(gross_profit) as profit')
            ->first();

        $returnsToday = \App\Models\CreditNote::where('status', \App\Enums\CreditNoteStatus::Completed->value)
            ->whereDate('created_at', today())
            ->get(['refund_amount', 'restock_value']);

        $returnRevenue      = (float) $returnsToday->sum('refund_amount');
        $returnProfitImpact = (float) $returnsToday->sum(
            fn ($cn) => (float) $cn->refund_amount - (float) $cn->restock_value
        );

        return [
            'count'   => $today->count ?? 0,
            'revenue' => max(0, (float) ($today->revenue ?? 0) - $returnRevenue),
            'profit'  => (float) ($today->profit ?? 0) - $returnProfitImpact,
        ];
    }

    public function openVoidModal(int $saleId): void
    {
        $this->voidSaleId = $saleId;
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    public function voidSale(int $saleId, VoidSaleAction $action): void
    {
        $this->requirePermission('sales.void');
        if (! Auth::user()->isOwner() && ! Auth::user()->can('sales.void')) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'You do not have permission to void sales.']);
            return;
        }

        $sale = Sale::findOrFail($saleId);

        if (! $sale->isVoidable()) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'This sale cannot be voided.']);
            return;
        }

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $action->execute($sale, $this->voidReason, $shop, Auth::user());
            $this->showVoidModal = false;
            $this->voidReason    = '';
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Sale {$sale->sale_number} voided."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $sales = Sale::with(['customer', 'branch', 'cashier', 'items', 'creditNotes'])
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