<?php

namespace App\Livewire;

use App\Reporting\DTOs\ExecutiveSummaryDTO;
use App\Reporting\Enums\ReportPeriod;
use App\Reporting\Services\ExecutiveDashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    #[Url(as: 'period')]
    public string $period = 'today';

    #[Url(as: 'branch')]
    public int $branchId = 0;

    public function updatedPeriod(): void {}
    public function updatedBranchId(): void {}

    #[Computed]
    public function summary(): ExecutiveSummaryDTO
    {
        $period = ReportPeriod::tryFrom($this->period) ?? ReportPeriod::Today;

        return app(ExecutiveDashboardService::class)->summary(
            shopId:   Auth::user()->shop_id,
            period:   $period,
            branchId: $this->branchId ?: null,
        );
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Branch::where('shop_id', Auth::user()->shop_id)
                                  ->where('is_active', true)->get();
    }

    #[Computed]
    public function selectedPeriodLabel(): string
    {
        return (ReportPeriod::tryFrom($this->period) ?? ReportPeriod::Today)->label();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}