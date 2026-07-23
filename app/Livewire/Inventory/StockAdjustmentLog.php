<?php
namespace App\Livewire\Inventory;

use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Stock Adjustment Log')]
class StockAdjustmentLog extends Component
{
    use \App\Traits\HasAuthorization, WithPagination;

    #[Url] public string $typeFilter = '';
    #[Url] public string $dateFrom   = '';
    #[Url] public string $dateTo     = '';

    public function mount(): void
    {
        $this->requirePermission('inventory.view');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo   = now()->format('Y-m-d');
    }

    #[Computed]
    public function adjustments()
    {
        return StockAdjustment::where('shop_id', Auth::user()->shop_id)
            ->with(['variant.product', 'productUnit', 'branch', 'createdBy', 'journalEntry'])
            ->when($this->typeFilter, fn ($q) => $q->where('adjustment_type', $this->typeFilter))
            ->when($this->dateFrom,  fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,    fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate(30);
    }

    #[Computed]
    public function summary(): object
    {
        $shopId = Auth::user()->shop_id;
        return (object) [
            'damaged'     => StockAdjustment::where('shop_id', $shopId)->where('adjustment_type', 'damaged')->sum('total_cost'),
            'written_off' => StockAdjustment::where('shop_id', $shopId)->where('adjustment_type', 'written_off')->sum('total_cost'),
        ];
    }

    public function render()
    {
        return view('livewire.inventory.stock-adjustment-log', [
            'adjustments' => $this->adjustments,
            'summary'     => $this->summary,
        ]);
    }
}