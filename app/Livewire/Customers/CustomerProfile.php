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
use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\CustomerDueFollowUp;
use App\Services\CustomerFollowUpService;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app')]
#[Title('Customer Profile')]
class CustomerProfile extends Component
{
    use HasAuthorization;
    use WithPagination;

    public Customer $customer;
    #[Url(as: 'tab')]
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

    // Follow-up form
    public bool   $showFollowUpForm     = false;
    public string $followupType         = '';
    public string $followupDate         = '';
    public string $followupStatus       = '';
    public string $promisedPaymentDate  = '';
    public string $promisedAmount       = '';
    public string $nextFollowupDate     = '';
    public string $customerResponse     = '';
    public string $internalNote         = '';

    public ?int   $editingFollowUpId       = null;
    public string $editNextFollowupDate    = '';
    public string $editPromisedPaymentDate = '';
    public string $editPromisedAmount      = '';
    public string $editStatus              = '';
    public string $editCustomerResponse    = '';
    public string $editInternalNote        = '';



    public function mount(Customer $customer): void
    {
        $this->requirePermission('customers.view');
        $this->customer = $customer->load(['guarantor', 'createdBy']);
        $this->followupDate = now()->format('Y-m-d\TH:i');
        $this->followupStatus = FollowUpStatus::Pending->value;
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

    #[Computed]
    public function openFollowUp(): ?CustomerDueFollowUp
    {
        return CustomerDueFollowUp::where('customer_id', $this->customer->id)
            ->whereNull('completed_at')
            ->whereNotIn('status', [FollowUpStatus::Paid->value, FollowUpStatus::Cancelled->value])
            ->latest('followup_date')
            ->latest('id')
            ->first();
    }

    #[Computed]
    public function timeline(): \Illuminate\Support\Collection
    {
        $transactions = CustomerTransaction::where('customer_id', $this->customer->id)
            ->latest()->limit(50)->get()
            ->map(fn (CustomerTransaction $t) => ['type' => 'transaction', 'at' => $t->created_at, 'model' => $t]);

        $followUps = CustomerDueFollowUp::where('customer_id', $this->customer->id)
            ->latest()->limit(50)->get()
            ->map(fn (CustomerDueFollowUp $f) => ['type' => 'followup', 'at' => $f->created_at, 'model' => $f]);

        return $transactions->concat($followUps)->sortByDesc('at')->values();
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

        
        $this->showPaymentForm = false;
        $this->paymentAmount = '';
        $this->paymentNotes = '';
        $this->paymentAccountId = 0;
        unset($this->recentTransactions);
        unset($this->openFollowUp, $this->timeline);

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

    public function addFollowUp(CustomerFollowUpService $service): void
    {
        $this->requirePermission('customers.manage_followups');

        $this->validate([
            'followupType'        => 'required|in:' . implode(',', array_column(FollowUpType::cases(), 'value')),
            'followupDate'        => 'required|date',
            'followupStatus'      => 'required|in:' . implode(',', array_column(FollowUpStatus::cases(), 'value')),
            'promisedPaymentDate' => 'nullable|date',
            'promisedAmount'      => 'nullable|numeric|min:0',
            'nextFollowupDate'    => 'nullable|date',
            'customerResponse'    => 'nullable|string|max:1000',
            'internalNote'        => 'nullable|string|max:1000',
        ]);

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        $service->create($this->customer, $shop, [
            'followup_type'         => $this->followupType,
            'followup_date'         => $this->followupDate,
            'status'                => $this->followupStatus,
            'promised_payment_date' => $this->promisedPaymentDate ?: null,
            'promised_amount'       => $this->promisedAmount !== '' ? (float) $this->promisedAmount : null,
            'next_followup_date'    => $this->nextFollowupDate ?: null,
            'customer_response'     => $this->customerResponse ?: null,
            'internal_note'         => $this->internalNote ?: null,
        ], Auth::user());

        $this->reset(['showFollowUpForm', 'followupType', 'promisedPaymentDate', 'promisedAmount', 'nextFollowupDate', 'customerResponse', 'internalNote']);
        $this->followupDate = now()->format('Y-m-d\TH:i');
        $this->followupStatus = FollowUpStatus::Pending->value;

        unset($this->openFollowUp, $this->timeline);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Follow-up recorded.']);
    }

    public function editFollowUp(int $id): void
    {
        $this->requirePermission('customers.manage_followups');

        $followUp = CustomerDueFollowUp::where('customer_id', $this->customer->id)->findOrFail($id);

        $this->editingFollowUpId       = $followUp->id;
        $this->editNextFollowupDate    = $followUp->next_followup_date?->format('Y-m-d\TH:i') ?? '';
        $this->editPromisedPaymentDate = $followUp->promised_payment_date?->format('Y-m-d\TH:i') ?? '';
        $this->editPromisedAmount      = $followUp->promised_amount !== null ? (string) $followUp->promised_amount : '';
        $this->editStatus              = $followUp->status->value;
        $this->editCustomerResponse    = $followUp->customer_response ?? '';
        $this->editInternalNote        = $followUp->internal_note ?? '';
    }

    public function updateFollowUp(CustomerFollowUpService $service): void
    {
        $this->requirePermission('customers.manage_followups');

        $this->validate([
            'editNextFollowupDate'    => 'nullable|date',
            'editPromisedPaymentDate' => 'nullable|date',
            'editPromisedAmount'      => 'nullable|numeric|min:0',
            'editStatus'              => 'required|in:' . implode(',', array_column(FollowUpStatus::cases(), 'value')),
            'editCustomerResponse'    => 'nullable|string|max:1000',
            'editInternalNote'        => 'nullable|string|max:1000',
        ]);

        $followUp = CustomerDueFollowUp::where('customer_id', $this->customer->id)->findOrFail($this->editingFollowUpId);

        $service->update($followUp, [
            'next_followup_date'    => $this->editNextFollowupDate ?: null,
            'promised_payment_date' => $this->editPromisedPaymentDate ?: null,
            'promised_amount'       => $this->editPromisedAmount !== '' ? (float) $this->editPromisedAmount : null,
            'status'                => $this->editStatus,
            'customer_response'     => $this->editCustomerResponse ?: null,
            'internal_note'         => $this->editInternalNote ?: null,
        ]);

        $this->editingFollowUpId = null;
        unset($this->openFollowUp, $this->timeline);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Follow-up updated.']);
    }

    public function cancelEditFollowUp(): void
    {
        $this->editingFollowUpId = null;
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

    public function delete(): void
    {
        $this->requirePermission('customers.delete');

        if (! $this->customer->isDeletable()) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'Cannot delete — customer has transaction history.']);
            return;
        }

        $this->customer->delete();
        $this->redirect(route('customers.index'), navigate: true);
    }
}