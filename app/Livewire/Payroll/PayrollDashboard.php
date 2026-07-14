<?php

namespace App\Livewire\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollLoan;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payroll')]
class PayrollDashboard extends Component
{
    use \App\Traits\HasAuthorization;

    public function mount(): void
    {
        $this->requirePermission('payroll.view');
    }

    #[Computed]
    public function stats(): object
    {
        $shopId = Auth::user()->shop_id;
        $now    = now();

        // Current month run
        $currentRun = PayrollRun::where('shop_id', $shopId)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->whereNotIn('status', ['cancelled', 'reversed'])
            ->latest()
            ->first();

        // Pending approvals
        $pendingRuns = PayrollRun::where('shop_id', $shopId)
            ->where('status', PayrollRunStatus::UnderReview->value)
            ->count();

        // Outstanding salary (approved/processing/partially paid)
        $outstandingSalary = PayrollSlip::where('shop_id', $shopId)
            ->whereIn('status', ['ready_for_payment', 'partially_paid'])
            ->sum('balance_payable');

        // Active loans
        $activeLoans = PayrollLoan::where('shop_id', $shopId)
            ->where('status', 'active')
            ->sum('outstanding_balance');

        // Last 6 months payroll trend
        $trend = DB::table('payroll_runs')
            ->where('shop_id', $shopId)
            ->whereIn('status', ['approved', 'processing_payment', 'partially_paid', 'paid'])
            ->where('year', '>=', $now->copy()->subMonths(5)->year)
            ->orderBy('year')
            ->orderBy('month')
            ->selectRaw('year, month, total_net_payable, total_paid, status')
            ->get()
            ->map(fn ($r) => (object) [
                'label'  => \Carbon\Carbon::createFromDate($r->year, $r->month, 1)->format('M Y'),
                'net'    => (float) $r->total_net_payable,
                'paid'   => (float) $r->total_paid,
                'status' => $r->status,
            ]);

        return (object) compact(
            'currentRun', 'pendingRuns', 'outstandingSalary', 'activeLoans', 'trend'
        );
    }

    #[Computed]
    public function recentRuns(): \Illuminate\Database\Eloquent\Collection
    {
        return PayrollRun::where('shop_id', Auth::user()->shop_id)
            ->with(['branch', 'department', 'generatedBy', 'approvedBy'])
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function pendingApprovals(): \Illuminate\Database\Eloquent\Collection
    {
        return PayrollRun::where('shop_id', Auth::user()->shop_id)
            ->where('status', PayrollRunStatus::UnderReview->value)
            ->with('generatedBy')
            ->latest()
            ->get();
    }

    #[Computed]
    public function outstandingSlips(): \Illuminate\Support\Collection
    {
        return PayrollSlip::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['ready_for_payment', 'partially_paid'])
            ->with(['payrollRun', 'user'])
            ->orderBy('balance_payable', 'desc')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.payroll.payroll-dashboard');
    }
}