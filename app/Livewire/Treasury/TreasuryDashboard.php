<?php

namespace App\Livewire\Treasury;

use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\RejectTreasuryTransactionAction;
use App\Enums\TreasuryTransactionCategory;
use App\Enums\TreasuryTransactionStatus;
use App\Models\TreasuryTransaction;
use App\Reporting\Repositories\FinancialRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Treasury')]
class TreasuryDashboard extends Component
{
    use WithPagination;
    use \App\Traits\HasAuthorization;

    #[Url]
    public string $statusFilter   = '';

    #[Url]
    public string $typeFilter     = '';

    #[Url]
    public string $dateFrom       = '';

    #[Url]
    public string $dateTo         = '';

    // Reject modal
    public bool   $showRejectModal = false;
    public ?int   $rejectingId     = null;
    public string $rejectionReason = '';

    // Reversal flow handled in TreasuryTransactionDetail

    public function mount(): void
    {
        $this->requirePermission('treasury.view');
    }

    #[Computed]
    public function cashPosition(): \Illuminate\Support\Collection
    {
        return app(FinancialRepository::class)
            ->cashPositionByAccount(Auth::user()->shop_id);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return TreasuryTransaction::where('shop_id', Auth::user()->shop_id)
            ->where('status', TreasuryTransactionStatus::PendingApproval->value)
            ->count();
    }

    #[Computed]
    public function categories(): array
    {
        return TreasuryTransactionCategory::cases();
    }

    public function approve(int $id, ApproveTreasuryTransactionAction $action): void
    {
        $this->requirePermission('treasury.approve');

        $txn = TreasuryTransaction::findOrFail($id);

        try {
            $action->execute($txn, Auth::user());
            $this->dispatch('notify', ['type' => 'success',
                'message' => "{$txn->transaction_number} approved and journal posted."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function openRejectModal(int $id): void
    {
        $this->requirePermission('treasury.approve');
        $this->rejectingId     = $id;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject(RejectTreasuryTransactionAction $action): void
    {
        $this->validate(['rejectionReason' => 'required|string|min:5']);

        $txn = TreasuryTransaction::findOrFail($this->rejectingId);

        try {
            $action->execute($txn, $this->rejectionReason, Auth::user());
            $this->showRejectModal = false;
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Transaction rejected.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteDraft(int $id): void
    {
        $txn = TreasuryTransaction::findOrFail($id);

        if (! $txn->isDraft()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Only draft transactions can be deleted.']);
            return;
        }

        if ($txn->created_by !== Auth::id() && ! Auth::user()->isOwner()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You can only delete your own drafts.']);
            return;
        }

        activity()->causedBy(Auth::user())->performedOn($txn)->log('treasury_transaction.deleted_draft');
        $txn->delete();

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Draft deleted.']);
    }

    public function render()
    {
        $shopId = Auth::user()->shop_id;

        $transactions = TreasuryTransaction::with([
            'fromAccount', 'toAccount', 'createdBy', 'approvedBy',
        ])
            ->where('shop_id', $shopId)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter,   fn ($q) => $q->where('transaction_category', $this->typeFilter))
            ->when($this->dateFrom,     fn ($q) => $q->where('transaction_date', '>=', $this->dateFrom))
            ->when($this->dateTo,       fn ($q) => $q->where('transaction_date', '<=', $this->dateTo))
            ->latest('transaction_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.treasury.treasury-dashboard', compact('transactions'));
    }
}