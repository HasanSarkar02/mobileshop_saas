<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\ReversePayrollPaymentAction;
use App\Models\PayrollSlip;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payslip')]
class PayrollSlipDetail extends Component
{
    use \App\Traits\HasAuthorization;

    public PayrollSlip $slip;

    public bool   $showReverseModal = false;
    public ?int   $reversingPaymentId = null;
    public string $reversalReason   = '';

    public function mount(PayrollSlip $slip): void
    {
        $this->requirePermission('payroll.view');

        if ($slip->shop_id !== Auth::user()->shop_id) abort(403);

        $this->slip = $slip->load([
            'payrollRun',
            'user',
            'earnings',
            'deductions',
            'activePayments.paymentAccount',
            'activePayments.journalEntry',
            'loanRecoveries.loan',
            'journalEntry.lines.account',
        ]);
    }

    public function openReversePayment(int $paymentId): void
    {
        $this->requirePermission('payroll.reverse');
        $this->reversingPaymentId = $paymentId;
        $this->reversalReason     = '';
        $this->showReverseModal   = true;
    }

    public function reversePayment(ReversePayrollPaymentAction $action): void
    {
        $this->validate(['reversalReason' => 'required|string|min:5']);

        $payment = \App\Models\PayrollPayment::where('slip_id', $this->slip->id)
            ->findOrFail($this->reversingPaymentId);

        try {
            $action->execute($payment, $this->reversalReason, Auth::user());
            $this->showReverseModal = false;
            $this->slip->refresh()->load([
                'activePayments.paymentAccount',
                'loanRecoveries.loan',
            ]);
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Payment reversed. Balance restored to ৳" .
                             number_format($this->slip->balance_payable, 0)]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.payroll.payroll-slip-detail');
    }
}