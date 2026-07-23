<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Services\SalesReportService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Sales Report')]
class SalesReport extends Component
{
    use HasReportFilter;
    use \App\Traits\HasAuthorization;

    public function mount(): void
    {
        $this->requirePermission('accounting.view_basic_reports');
    }
    #[Url(as: 'view')]
    public string $activeView = 'overview'; // overview | products | customers | employees | payment

    #[Computed]
    public function summary()
    {
        return app(SalesReportService::class)->summary($this->buildFilter());
    }

    #[Computed]
    public function dailyTrend(): Collection
    {
        return app(SalesReportService::class)->dailyTrend($this->buildFilter());
    }

    #[Computed]
    public function topProducts(): Collection
    {
        return app(SalesReportService::class)->topProducts($this->buildFilter(), 25);
    }

    #[Computed]
    public function topCustomers(): Collection
    {
        return app(SalesReportService::class)->topCustomers($this->buildFilter(), 25);
    }

    #[Computed]
    public function byEmployee(): Collection
    {
        return app(SalesReportService::class)->byEmployee($this->buildFilter());
    }

    #[Computed]
    public function byPaymentMethod(): Collection
    {
        return app(SalesReportService::class)->byPaymentMethod($this->buildFilter());
    }

    #[Computed]
    public function byBranch(): Collection
    {
        return app(SalesReportService::class)->byBranch($this->buildFilter());
    }

    public function render()
    {
        return view('livewire.reports.sales-report', [
            'branches'    => $this->getBranchesProperty(),
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}