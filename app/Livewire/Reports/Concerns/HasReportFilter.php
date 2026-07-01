<?php

namespace App\Livewire\Reports\Concerns;

use App\Reporting\DTOs\DateRange;
use App\Reporting\DTOs\ReportFilter;
use App\Reporting\Enums\ReportPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

trait HasReportFilter
{
    #[Url(as: 'period')]
    public string $period   = 'this_month';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo   = '';

    #[Url(as: 'branch')]
    public int    $branchId = 0;

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->dateFrom = '';
            $this->dateTo   = '';
        }
    }

    protected function buildFilter(): ReportFilter
    {
        $shopId = Auth::user()->shop_id;

        if ($this->period === 'custom' && $this->dateFrom && $this->dateTo) {
            return ReportFilter::forShopAndDateRange($shopId, $this->dateFrom, $this->dateTo);
        }

        $resolvedPeriod = ReportPeriod::tryFrom($this->period) ?? ReportPeriod::ThisMonth;

        return new ReportFilter(
            shopId:    $shopId,
            dateRange: $resolvedPeriod->toDateRange(),
            branchId:  $this->branchId ?: null,
            period:    $resolvedPeriod,
        );
    }

    protected function periodLabel(): string
    {
        if ($this->period === 'custom' && $this->dateFrom && $this->dateTo) {
            return DateRange::custom($this->dateFrom, $this->dateTo)->toDisplayString();
        }

        return (ReportPeriod::tryFrom($this->period) ?? ReportPeriod::ThisMonth)->label();
    }

    public function getBranchesProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Branch::where('shop_id', Auth::user()->shop_id)
                                  ->where('is_active', true)->get();
    }
}