<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\DTOs\ProfitLossDTO;
use App\Reporting\Services\FinancialReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Profit & Loss')]
class ProfitLossReport extends Component
{
    use HasReportFilter;

    #[Computed]
    public function report(): ProfitLossDTO
    {
        return app(FinancialReportService::class)
            ->profitAndLoss($this->buildFilter(), withPrevious: true);
    }

    public function render()
    {
        return view('livewire.reports.profit-loss-report', [
            'branches'    => $this->getBranchesProperty(),
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}