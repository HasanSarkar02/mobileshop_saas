<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\ProcessPayrollPaymentAction;
use App\Models\PaymentAccount;
use App\Models\PayrollSlip;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pay Employee')]
class PayEmployeesForm extends Component
{
    use \App\Traits\HasAuthorization;

    public PayrollSlip $slip;

    public string $amount           = '';
    public int    $paymentAccountId = 0;
    public string $paymentMethod    = 'cash';
    public string $paymentDate      = '';
    public string $referenceNumber  = '';
    public string $notes            = '';

    // Balance display flags (public bool for wire:show compatibility)
    public bool   $showBalanceWarning  = false;
    public string $balanceWarningText  = '';
    public float  $accountBalance      = 0;

    public function mount(PayrollSlip $slip): void
    {
        $this->requirePermission('payroll.pay');

        if ($slip->shop_id !== Auth::user()->shop_id) abort(403);

        if (! $slip->status->canAcceptPayment()) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => "This slip cannot accept payments (status: {$slip->status->label()})."]);
            $this->redirect(route('payroll.slip.show', $slip), navigate: true);
            return;
        }

        $this->slip           = $slip->load(['payrollRun', 'user', 'paymentAccount', 'activePayments']);
        $this->paymentDate    = now()->format('Y-m-d');
        $this->amount         = number_format((float) $slip->balance_payable, 2, '.', '');

        // Pre-fill from employee's salary structure preference
        if ($slip->payment_account_id) {
            $this->paymentAccountId = $slip->payment_account_id;
            $this->paymentMethod    = $slip->payment_method ?? 'cash';
            $this->loadAccountBalance();
        }
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->get();
    }

    public function updatedPaymentAccountId(): void
    {
        $this->loadAccountBalance();
    }

    public function updatedAmount(): void
    {
        $this->checkSufficiency();
    }

    private function loadAccountBalance(): void
    {
        if (! $this->paymentAccountId) {
            $this->accountBalance   = 0;
            $this->showBalanceWarning = false;
            return;
        }

        $this->accountBalance = app(\App\Services\AccountBalanceChecker::class)
            ->currentBalance($this->paymentAccountId);

        $this->checkSufficiency();
    }

    private function checkSufficiency(): void
    {
        if (! $this->paymentAccountId || ! $this->amount) {
            $this->showBalanceWarning = false;
            return;
        }

        $needed = (float) $this->amount;
        $available = $this->accountBalance;

        if ($available < $needed) {
            $this->showBalanceWarning = true;
            $this->balanceWarningText = "Insufficient balance. Available: ৳" .
                number_format($available, 2) . " — Short by ৳" .
                number_format($needed - $available, 2);
        } else {
            $this->showBalanceWarning = false;
        }
    }

    public function payFull(): void
    {
        $this->amount = number_format((float) $this->slip->balance_payable, 2, '.', '');
        $this->checkSufficiency();
    }

    public function save(ProcessPayrollPaymentAction $action): void
    {
        $this->validate([
            'amount'           => 'required|numeric|min:0.01',
            'paymentAccountId' => 'required|integer|min:1',
            'paymentDate'      => 'required|date',
            'paymentMethod'    => 'required|string',
        ], [
            'paymentAccountId.min' => 'Please select a payment account.',
        ]);

        try {
            $payment = $action->execute($this->slip, [
                'amount'             => (float) $this->amount,
                'payment_account_id' => $this->paymentAccountId,
                'payment_method'     => $this->paymentMethod,
                'payment_date'       => $this->paymentDate,
                'reference_number'   => $this->referenceNumber ?: null,
                'notes'              => $this->notes ?: null,
            ], Auth::user());

            $this->slip->refresh();

            $msg = "Payment of ৳" . number_format($payment->amount, 0) .
                   " recorded ({$payment->payment_number}).";

            if ((float) $this->slip->balance_payable <= 0) {
                $msg .= " Slip is now fully paid. ✓";
            } else {
                $msg .= " Remaining: ৳" . number_format($this->slip->balance_payable, 0);
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);

            // If fully paid, go back to slip detail
            if ((float) $this->slip->balance_payable <= 0) {
                $this->redirect(route('payroll.slip.show', $this->slip), navigate: true);
                return;
            }

            // Reset for next partial payment
            $this->amount          = number_format((float) $this->slip->balance_payable, 2, '.', '');
            $this->referenceNumber = '';
            $this->notes           = '';
            $this->checkSufficiency();

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.payroll.pay-employees-form');
    }
}