<?php

namespace App\Livewire\Treasury;

use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\RejectTreasuryTransactionAction;
use App\Actions\Treasury\ReverseTreasuryTransactionAction;
use App\Enums\TreasuryTransactionStatus;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Transaction Detail')]
class TreasuryTransactionDetail extends Component
{
    use \App\Traits\HasAuthorization;

    public TreasuryTransaction $transaction;

    // Reject modal
    public bool   $showRejectModal  = false;
    public string $rejectionReason  = '';

    // Reversal modal
    public bool   $showReverseModal = false;
    public string $reversalReason   = '';

    public function mount(TreasuryTransaction $transaction): void
    {
        $this->requirePermission('treasury.view');

        if ($transaction->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $this->transaction = $transaction->load([
            'fromAccount', 'toAccount',
            'journalEntry.lines.account',
            'createdBy', 'approvedBy', 'rejectedBy',
            'reversalOf', 'reversedBy',
            'branch',
        ]);
    }

    public function approve(ApproveTreasuryTransactionAction $action): void
    {
        $this->requirePermission('treasury.approve');

        try {
            $action->execute($this->transaction, Auth::user());
            $this->transaction->refresh()->load(['journalEntry.lines.account', 'approvedBy']);
            $this->dispatch('notify', ['type' => 'success',
                'message' => "{$this->transaction->transaction_number} approved and posted."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reject(RejectTreasuryTransactionAction $action): void
    {
        $this->requirePermission('treasury.approve');
        $this->validate(['rejectionReason' => 'required|string|min:5']);

        try {
            $action->execute($this->transaction, $this->rejectionReason, Auth::user());
            $this->showRejectModal = false;
            $this->transaction->refresh();
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Transaction rejected.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reverse(ReverseTreasuryTransactionAction $action): void
    {
        $this->requirePermission('treasury.reverse');
        $this->validate(['reversalReason' => 'required|string|min:10']);

        try {
            $reversal = $action->execute($this->transaction, $this->reversalReason, Auth::user());
            $this->showReverseModal = false;
            $this->transaction->refresh();
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Reversed. Reversal document: {$reversal->transaction_number}"]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.treasury.transaction-detail');
    }
}