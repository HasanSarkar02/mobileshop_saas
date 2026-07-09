<?php

namespace App\Livewire\Treasury;

use App\Enums\TreasuryTransactionStatus;
use App\Enums\TreasuryTransactionType;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Edit Draft')]
class TreasuryTransactionEdit extends Component
{
    use WithFileUploads;
    use \App\Traits\HasAuthorization;

    public TreasuryTransaction $transaction;

    public string $amount          = '';
    public string $feeAmount       = '0';
    public string $transactionDate = '';
    public string $description     = '';
    public string $referenceNumber = '';
    public string $thirdPartyName  = '';
    public string $notes           = '';
    public $attachment             = null;

    public function mount(TreasuryTransaction $transaction): void
    {
        $this->requirePermission('treasury.view');

        if ($transaction->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        if (! $transaction->isDraft()) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'Only draft transactions can be edited.']);
            $this->redirect(route('treasury.show', $transaction), navigate: true);
            return;
        }

        $this->transaction     = $transaction;
        $this->amount          = (string) $transaction->amount;
        $this->feeAmount       = (string) $transaction->fee_amount;
        $this->transactionDate = $transaction->transaction_date->format('Y-m-d');
        $this->description     = $transaction->description;
        $this->referenceNumber = $transaction->reference_number ?? '';
        $this->thirdPartyName  = $transaction->third_party_name ?? '';
        $this->notes           = $transaction->notes ?? '';
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)->get();
    }

    public function save(): void
    {
        $this->validate([
            'amount'          => 'required|numeric|min:0.01',
            'transactionDate' => 'required|date',
            'description'     => 'required|string|min:3|max:500',
            'feeAmount'       => 'nullable|numeric|min:0',
        ]);

        $attachmentPath = null;
        if ($this->attachment) {
            $this->validate(['attachment' => 'file|max:5120|mimes:jpg,jpeg,png,pdf']);
            $attachmentPath = $this->attachment->store(
                "shops/" . Auth::user()->shop_id . "/treasury", 'public'
            );
        }

        $amount  = (float) $this->amount;
        $fee     = (float) ($this->feeAmount ?: 0);

        $updates = [
            'amount'           => $amount,
            'fee_amount'       => $fee,
            'net_amount'       => $amount - $fee,
            'transaction_date' => $this->transactionDate,
            'description'      => $this->description,
            'reference_number' => $this->referenceNumber ?: null,
            'third_party_name' => $this->thirdPartyName ?: null,
            'notes'            => $this->notes ?: null,
            'updated_by'       => Auth::id(),
        ];

        if ($attachmentPath) {
            $existing = $this->transaction->attachments ?? [];
            $updates['attachments'] = array_merge($existing, [$attachmentPath]);
        }

        $this->transaction->update($updates);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Draft updated.']);
        $this->redirect(route('treasury.show', $this->transaction), navigate: true);
    }

    public function render()
    {
        return view('livewire.treasury.transaction-edit');
    }
}