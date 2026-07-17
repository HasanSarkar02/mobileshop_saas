<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Repositories\InventoryRepository;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Stock Valuation')]
class StockValuationReport extends Component
{
    use HasReportFilter;
    use \App\Traits\HasAuthorization;

    public function mount(): void
{
    $this->requirePermission('reports.view');
}
    #[Computed]
    public function valuationData(): Collection
    {
        return app(InventoryRepository::class)->stockValuation($this->buildFilter());
    }

    #[Computed]
    public function summary(): object
    {
        return app(InventoryRepository::class)->totalValue(
            \Illuminate\Support\Facades\Auth::user()->shop_id,
            $this->branchId ?: null,
        );
    }

    public function render()
    {
        return view('livewire.reports.stock-valuation-report', [
            'branches'    => $this->getBranchesProperty(),
            'periodLabel' => now()->format('d M Y H:i'),
        ]);
    }
}