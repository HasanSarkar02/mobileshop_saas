<?php

namespace App\Livewire\Customers;

use App\Events\CustomerDueReminderRequested;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\PaymentAccount;
use App\Services\CustomerLedgerService;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Events\CustomerPaymentRecorded;

#[Layout('components.layouts.app')]
#[Title('Customer Profile')]
class CustomerProfile extends Component
{
    use HasAuthorization;
    use WithPagination;

    public Customer $customer;
    public string $activeTab = 'overview';

    // Payment form
    public bool   $showPaymentForm = false;
    public string $paymentAmount   = '';
    public int    $paymentAccountId = 0;
    public string $paymentNotes    = '';

    // Write-off form
    public bool   $showWriteOffForm = false;
    public string $writeOffAmount   = '';
    public string $writeOffNotes    = '';

    public function mount(Customer $customer): void
    {
        $this->requirePermission('customers.view');
        $this->customer = $customer->load(['guarantor', 'createdBy']);
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->get();
    }

    #[Computed]
    public function recentTransactions(): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerTransaction::where('customer_id', $this->customer->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function recordPayment(CustomerLedgerService $ledger): void
    {
        $this->requirePermission('customers.edit');
        $this->validate([
            'paymentAmount'    => 'required|numeric|min:1|max:' . $this->customer->current_balance,
            'paymentAccountId' => 'required|integer|min:1',
        ], [
            'paymentAmount.max' => "Amount cannot exceed current balance ৳" . number_format($this->customer->current_balance, 2),
            'paymentAccountId.min' => 'Please select a payment account.',
        ]);

        $paymentAccount = PaymentAccount::findOrFail($this->paymentAccountId);

       $transaction = $ledger->recordPayment(
            customer: $this->customer,
            amount: (float) $this->paymentAmount,
            paymentAccount: $paymentAccount,
            notes: $this->paymentNotes ?: null,
            actor: Auth::user(),
        );

        $this->customer->refresh();

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        event(new CustomerPaymentRecorded($transaction, $this->customer, $shop));
        
        $this->showPaymentForm = false;
        $this->paymentAmount = '';
        $this->paymentNotes = '';
        $this->paymentAccountId = 0;
        unset($this->recentTransactions);

        $this->dispatch('notify', type: 'success',
            message: "Payment of ৳" . number_format((float) $this->paymentAmount, 2) . " recorded.");
    }

    public function confirmWriteOff(CustomerLedgerService $ledger): void
    {
        $this->requirePermission('customers.edit');
        $this->validate([
            'writeOffAmount' => 'required|numeric|min:1',
            'writeOffNotes'  => 'required|string|min:5',
        ], [
            'writeOffNotes.min' => 'Please provide a reason for the write-off (min 5 characters).',
        ]);

        $ledger->writeOff(
            customer: $this->customer,
            amount: (float) $this->writeOffAmount,
            notes: $this->writeOffNotes,
            actor: Auth::user(),
        );

        $this->customer->refresh();
        $this->showWriteOffForm = false;
        $this->writeOffAmount = '';
        $this->writeOffNotes = '';
        unset($this->recentTransactions);

        $this->dispatch('notify', type: 'warning', message: 'Bad debt written off.');
    }

    public function render()
    {
        $transactions = CustomerTransaction::where('customer_id', $this->customer->id)
            ->with('createdBy')
            ->latest()
            ->paginate(15, pageName: 'tPage');

        $sales = \App\Models\Sale::withoutGlobalScopes()
            ->where('shop_id', $this->customer->shop_id)
            ->where('customer_id', $this->customer->id)
            ->with(['items.variant.product.brand', 'items.productUnit', 'payments'])
            ->latest('confirmed_at')
            ->paginate(10, pageName: 'sPage');

        return view('livewire.customers.customer-profile',
            compact('transactions', 'sales'));
    }

    public function sendDueReminder(): void
    {
        if ($this->customer->current_balance <= 0) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No outstanding balance.']);
            return;
        }

        $shop   = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        event(new CustomerDueReminderRequested(
            shop: $shop,
            customer: $this->customer,
        ));

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Due reminder queued.',
        ]);
    }
}