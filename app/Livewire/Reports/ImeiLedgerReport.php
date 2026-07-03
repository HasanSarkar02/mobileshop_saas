<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Repositories\InventoryRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('IMEI Ledger')]
class ImeiLedgerReport extends Component
{
    use HasReportFilter, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $unitStatus = '';

    #[Url(as: 'cat')]
    public int $categoryId = 0;

    #[Url(as: 'brand')]
    public int $brandId = 0;

    public function updatingSearch(): void   { $this->resetPage(); }
    public function updatingUnitStatus(): void { $this->resetPage(); }

    #[Computed]
    public function imeiCounts()
    {
        return app(InventoryRepository::class)->imeiStatusCounts(
            Auth::user()->shop_id,
            $this->branchId ?: null,
        );
    }

    public function render()
    {
        $filter = new \App\Reporting\DTOs\ReportFilter(
            shopId:     Auth::user()->shop_id,
            dateRange:  \App\Reporting\DTOs\DateRange::custom('2000-01-01', now()->toDateString()),
            branchId:   $this->branchId ?: null,
            status:     $this->unitStatus ?: null,
            categoryId: $this->categoryId ?: null,
            brandId:    $this->brandId ?: null,
            perPage:    30,
        );

        $records = app(InventoryRepository::class)->imeiLedger($filter, $this->search);

        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();
        $brands     = \App\Models\Brand::where('is_active', true)->orderBy('name')->get();

        return view('livewire.reports.imei-ledger-report', compact('records', 'categories', 'brands'));
    }
}