<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Repositories\FinancialRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Cash Flow Statement')]
class CashFlowReport extends Component
{
    use HasReportFilter;

    #[Computed]
    public function cashFlow(): array
    {
        return app(FinancialRepository::class)->cashFlow(
            Auth::user()->shop_id,
            $this->buildFilter()
        );
    }

    public function render()
    {
        return view('livewire.reports.cash-flow-report', [
            'branches'    => $this->getBranchesProperty(),
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}