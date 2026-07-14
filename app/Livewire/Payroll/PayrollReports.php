<?php

namespace App\Livewire\Payroll;

use App\Enums\PayrollRunStatus;
use App\Models\Department;
use App\Models\PayrollLoan;
use App\Models\PayrollPayment;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Payroll Reports')]
class PayrollReports extends Component
{
    use \App\Traits\HasAuthorization, WithPagination;

    #[Url]
    public string $activeReport = 'outstanding';

    #[Url]
    public string $yearMonth    = '';

    #[Url]
    public int    $userId       = 0;

    public function mount(): void
    {
        $this->requirePermission('payroll.view');

        if (! $this->yearMonth) {
            $this->yearMonth = now()->format('Y-m');
        }
    }

    private function periodParts(): array
    {
        [$year, $month] = explode('-', $this->yearMonth . '-0');
        return [(int) $year, (int) $month];
    }

    // ── Outstanding Salary ─────────────────────────────────────────────────────

    #[Computed]
    public function outstandingSlips()
    {
        return PayrollSlip::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['ready_for_payment', 'partially_paid'])
            ->with(['payrollRun', 'user'])
            ->orderBy('balance_payable', 'desc')
            ->paginate(25);
    }

    #[Computed]
    public function outstandingTotal(): float
    {
        return (float) PayrollSlip::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['ready_for_payment', 'partially_paid'])
            ->sum('balance_payable');
    }

    // ── Payment Register ───────────────────────────────────────────────────────

    #[Computed]
    public function paymentRegister()
    {
        [$year, $month] = $this->periodParts();

        return PayrollPayment::where('shop_id', Auth::user()->shop_id)
            ->where('status', 'paid')
            ->when($year, fn ($q) =>
                $q->whereYear('payment_date', $year)
            )
            ->when($month, fn ($q) =>
                $q->whereMonth('payment_date', $month)
            )
            ->with(['slip.user', 'paymentAccount', 'payrollRun'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(30);
    }

    #[Computed]
    public function paymentRegisterTotal(): float
    {
        [$year, $month] = $this->periodParts();

        return (float) PayrollPayment::where('shop_id', Auth::user()->shop_id)
            ->where('status', 'paid')
            ->when($year,  fn ($q) => $q->whereYear('payment_date', $year))
            ->when($month, fn ($q) => $q->whereMonth('payment_date', $month))
            ->sum('amount');
    }

    // ── Loan Recovery Report ───────────────────────────────────────────────────

    #[Computed]
    public function activeLoans()
    {
        return PayrollLoan::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['active', 'fully_recovered'])
            ->with(['user', 'recoveries'])
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->orderByDesc('disbursement_date')
            ->paginate(20);
    }

    // ── Payroll Audit Report ───────────────────────────────────────────────────

    #[Computed]
    public function auditLogs()
    {
        [$year, $month] = $this->periodParts();

        return \App\Models\PayrollAuditLog::where('shop_id', Auth::user()->shop_id)
            ->with('user')
            ->when($year,  fn ($q) => $q->whereYear('created_at', $year))
            ->when($month, fn ($q) => $q->whereMonth('created_at', $month))
            ->orderByDesc('created_at')
            ->paginate(30);
    }

    // ── Employees dropdown ─────────────────────────────────────────────────────

    #[Computed]
    public function employees(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\User::where('shop_id', Auth::user()->shop_id)
            ->where('user_type', 'employee')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.payroll.payroll-reports', [
            'outstandingSlips'     => $this->activeReport === 'outstanding'   ? $this->outstandingSlips     : null,
            'outstandingTotal'     => $this->activeReport === 'outstanding'   ? $this->outstandingTotal     : 0,
            'paymentRegister'      => $this->activeReport === 'payments'      ? $this->paymentRegister      : null,
            'paymentRegisterTotal' => $this->activeReport === 'payments'      ? $this->paymentRegisterTotal : 0,
            'activeLoans'          => $this->activeReport === 'loans'         ? $this->activeLoans          : null,
            'auditLogs'            => $this->activeReport === 'audit'         ? $this->auditLogs            : null,
            'employees'            => $this->employees,
        ]);
    }
}