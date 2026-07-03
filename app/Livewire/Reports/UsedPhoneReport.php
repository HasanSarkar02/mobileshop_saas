<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Repositories\UsedPhoneRepository;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Used Phone Report')]
class UsedPhoneReport extends Component
{
    use HasReportFilter, WithPagination;

    #[Url(as: 'view')]
    public string $activeView = 'summary';

    #[Computed]
    public function summary(): object
    {
        return app(UsedPhoneRepository::class)->summary(
            \Illuminate\Support\Facades\Auth::user()->shop_id,
            $this->branchId ?: null,
        );
    }

    #[Computed]
    public function conditionBreakdown()
    {
        return app(UsedPhoneRepository::class)->conditionBreakdown(
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
            perPage:   25,
        );

        $acquisitions = $this->activeView === 'list'
            ? app(UsedPhoneRepository::class)->list($filter)
            : null;

        return view('livewire.reports.used-phone-report', [
            'branches'     => $this->getBranchesProperty(),
            'periodLabel'  => $this->periodLabel(),
            'acquisitions' => $acquisitions,
        ]);
    }
}