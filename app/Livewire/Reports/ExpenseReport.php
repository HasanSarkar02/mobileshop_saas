<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Repositories\ExpenseRepository;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Expense Report')]
class ExpenseReport extends Component
{
    use HasReportFilter, WithPagination;
    use \App\Traits\HasAuthorization;

    public function mount(): void
{
    $this->requirePermission('reports.view');
}

    #[Url(as: 'view')]
    public string $activeView = 'summary';

    public function updatingPeriod(): void   { $this->resetPage(); }
    public function updatingBranchId(): void { $this->resetPage(); }

    #[Computed]
    public function aggregate(): object
    {
        return app(ExpenseRepository::class)->aggregate($this->buildFilter());
    }

    #[Computed]
    public function byCategory(): Collection
    {
        return app(ExpenseRepository::class)->byCategory($this->buildFilter());
    }

    #[Computed]
    public function byBranch(): Collection
    {
        return app(ExpenseRepository::class)->byBranch($this->buildFilter());
    }

    #[Computed]
    public function trend(): Collection
    {
        return app(ExpenseRepository::class)->trend($this->buildFilter());
    }

    #[Computed]
    public function pendingCount(): int
    {
        return app(ExpenseRepository::class)->pendingApprovalCount(
            \Illuminate\Support\Facades\Auth::user()->shop_id
        );
    }

    public function render()
    {
        $filter = $this->buildFilter();
        $filter = new \App\Reporting\DTOs\ReportFilter(
            shopId:    $filter->shopId,
            dateRange: $filter->dateRange,
            branchId:  $filter->branchId,
            period:    $filter->period,
            perPage:   50,
        );

        $expenses = $this->activeView === 'list'
            ? app(ExpenseRepository::class)->list($filter)
            : null;

        return view('livewire.reports.expense-report', [
            'branches'    => $this->getBranchesProperty(),
            'periodLabel' => $this->periodLabel(),
            'expenses'    => $expenses,
        ]);
    }
}