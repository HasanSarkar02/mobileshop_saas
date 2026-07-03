<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Repositories\ServiceRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Service Report')]
class ServiceReport extends Component
{
    use HasReportFilter;

    #[Url(as: 'view')]
    public string $activeView = 'overview';

    #[Computed]
    public function stats(): object
    {
        return app(ServiceRepository::class)->stats(
            Auth::user()->shop_id,
            $this->branchId ?: null,
        );
    }

    #[Computed]
    public function periodRevenue(): float
    {
        return app(ServiceRepository::class)->revenueInPeriod($this->buildFilter());
    }

    #[Computed]
    public function technicianPerformance(): Collection
    {
        return app(ServiceRepository::class)->technicianPerformance($this->buildFilter());
    }

    #[Computed]
    public function openTickets(): Collection
    {
        return app(ServiceRepository::class)->openTickets(
            Auth::user()->shop_id,
            $this->branchId ?: null,
        );
    }

    #[Computed]
    public function statusBreakdown(): Collection
    {
        return DB::table('service_tickets')
            ->where('shop_id', Auth::user()->shop_id)
            ->selectRaw('status, COUNT(*) AS count, COALESCE(SUM(total_charge), 0) AS revenue')
            ->groupBy('status')
            ->get();
    }

    public function render()
    {
        return view('livewire.reports.service-report', [
            'branches'    => $this->getBranchesProperty(),
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}