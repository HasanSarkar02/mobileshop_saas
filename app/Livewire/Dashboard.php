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
        $user = \Illuminate\Support\Facades\Auth::user();

        // Employees get a limited view — no revenue, profit, or cash details
        if ($user->isEmployee()) {
            return view('livewire.employee-dashboard', [
                'employeeSales'   => $this->employeeSales($user),
                'myTickets'       => $this->myTickets($user),
                'pendingExpenses' => $this->pendingExpenses($user),
            ]);
        }

        // Owner / SuperAdmin — full executive summary
        return view('livewire.dashboard', [
            'branches'    => $this->branches,
            'periodLabel' => $this->selectedPeriodLabel,
        ]);
    }

    private function employeeSales(\App\Models\User $user): array
    {
        $today = \App\Models\Sale::where('cashier_id', $user->id)
            ->where('status', 'confirmed')
            ->whereDate('confirmed_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total), 0) as revenue')
            ->first();

        $month = \App\Models\Sale::where('cashier_id', $user->id)
            ->where('status', 'confirmed')
            ->whereMonth('confirmed_at', now()->month)
            ->whereYear('confirmed_at', now()->year)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total), 0) as revenue')
            ->first();

        return [
            'today_count'    => (int)   ($today->count   ?? 0),
            'today_revenue'  => (float) ($today->revenue ?? 0),
            'month_count'    => (int)   ($month->count   ?? 0),
            'month_revenue'  => (float) ($month->revenue ?? 0),
        ];
    }

    private function myTickets(\App\Models\User $user): \Illuminate\Support\Collection
    {
        return \App\Models\ServiceTicket::where('technician_id', $user->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest()
            ->take(8)
            ->get(['id', 'ticket_number', 'customer_name', 'device_model', 'status', 'amount_due']);
    }

    private function pendingExpenses(\App\Models\User $user): int
    {
        $user = Auth::user();
        if (! $user->isOwner() && ! $user->can('expenses.approve')) {
            return 0;
        }
        // Show only expenses created by this employee that are pending
        return \App\Models\Expense::where('created_by', $user->id)
            ->where('status', 'pending')
            ->count();
    }
}