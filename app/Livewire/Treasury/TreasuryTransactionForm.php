<?php

namespace App\Livewire\Treasury;

use App\Actions\Treasury\CreateTreasuryTransactionAction;
use App\Enums\TreasuryTransactionCategory;
use App\Enums\TreasuryTransactionType;
use App\Models\Branch;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('New Treasury Transaction')]
class TreasuryTransactionForm extends Component
{
    use WithFileUploads;
    use \App\Traits\HasAuthorization;

    // ── Form State ─────────────────────────────────────────────────────────────
    public string $transactionCategory = '';
    public string $transactionType     = '';

    public int    $fromAccountId   = 0;
    public int    $toAccountId     = 0;
    public string $amount          = '';
    public string $feeAmount       = '0';
    public string $transactionDate = '';
    public string $valueDate       = '';
    public string $description     = '';
    public string $referenceNumber = '';
    public string $thirdPartyName  = '';
    public string $thirdPartyRef   = '';
    public string $notes           = '';
    public int    $branchId        = 0;
    public $attachment             = null;
    public ?float $fromAccountBalance = null;
    public ?float $toAccountBalance   = null;

    // ── Reactive flags — MUST be public properties, NOT #[Computed] ───────────
    // wire:show="needsFromAccount" requires a real public property.
    // #[Computed] methods cannot be called by name in wire:show in Livewire v4.
    public bool   $needsFromAccount   = false;
    public bool   $needsToAccount     = false;
    public bool   $needsFee           = false;
    public bool   $needsThirdParty    = false;
    public bool   $willRequireApproval = false;
    public string $feeLabelText       = 'Fee (৳)';

    public function mount(): void
    {
        $this->requirePermission('treasury.view');

        $this->transactionDate = now()->format('Y-m-d');
        $this->branchId        = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)
                ->where('is_main', true)->value('id')
            ?? 0
        );
    }

    // ── Lifecycle Hooks ────────────────────────────────────────────────────────

    public function updatedTransactionCategory(): void
    {
        // Reset type and derived flags when category changes
        $this->transactionType  = '';
        $this->fromAccountId    = 0;
        $this->toAccountId      = 0;
        $this->feeAmount        = '0';
        $this->thirdPartyName   = '';
        $this->needsFromAccount  = false;
        $this->needsToAccount    = false;
        $this->needsFee          = false;
        $this->needsThirdParty   = false;
        $this->willRequireApproval = false;
        $this->feeLabelText      = 'Fee (৳)';
    }

    public function updatedTransactionType(): void
    {
        $type = TreasuryTransactionType::tryFrom($this->transactionType);

        // Reset accounts and amounts when type changes
        $this->fromAccountId  = 0;
        $this->toAccountId    = 0;
        $this->feeAmount      = '0';
        $this->thirdPartyName = '';

        // Update reactive flags based on the selected type
        $this->needsFromAccount  = $type?->needsFromAccount() ?? false;
        $this->needsToAccount    = $type?->needsToAccount()   ?? false;
        $this->needsFee          = $type?->needsFee()         ?? false;
        $this->needsThirdParty   = $type?->needsThirdParty()  ?? false;
        $this->feeLabelText      = $type?->feeLabel()         ?? 'Fee (৳)';

        $this->recalculateApprovalFlag();
    }

    public function updatedAmount(): void
    {
        $this->recalculateApprovalFlag();
    }

     public function updatedFromAccountId(): void
    {
        $this->fromAccountBalance = $this->fromAccountId
            ? app(\App\Services\AccountBalanceChecker::class)
                ->currentBalance($this->fromAccountId)
            : null;
        $this->recalculateApprovalFlag();
    }

    public function updatedToAccountId(): void
    {
        $this->toAccountBalance = $this->toAccountId
            ? app(\App\Services\AccountBalanceChecker::class)
                ->currentBalance($this->toAccountId)
            : null;
    }

    private function recalculateApprovalFlag(): void
    {
        $type = TreasuryTransactionType::tryFrom($this->transactionType);

        if (! $type) {
            $this->willRequireApproval = false;
            return;
        }

        // Equity and loan types always require Owner approval
        if ($type->alwaysRequiresApproval()) {
            $this->willRequireApproval = true;
            return;
        }

        // Cash adjustments by non-Owner always require approval
        if (
            in_array($type, [
                TreasuryTransactionType::CashOver,
                TreasuryTransactionType::CashShort,
            ]) && ! Auth::user()->isOwner()
        ) {
            $this->willRequireApproval = true;
            return;
        }

        // Amount exceeds threshold
        $threshold = (float) (Auth::user()->shop?->treasury_approval_threshold ?? 0);
        $this->willRequireApproval = $threshold > 0 && (float) ($this->amount ?: 0) > $threshold;
    }

    // ── Computed properties (safe — only accessed via $this-> in Blade, never wire:show) ──

    #[Computed]
    public function typeEnum(): ?TreasuryTransactionType
    {
        return $this->transactionType
            ? TreasuryTransactionType::tryFrom($this->transactionType)
            : null;
    }

    #[Computed]
    public function netAmount(): float
    {
        return max(0, (float) ($this->amount ?: 0) - (float) ($this->feeAmount ?: 0));
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->orderBy('provider')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->get();
    }

    #[Computed]
    public function typesByCategory(): array
    {
        $result = [];
        foreach (TreasuryTransactionCategory::cases() as $cat) {
            $types = array_values(array_filter(
                TreasuryTransactionType::cases(),
                fn ($t) => $t->category() === $cat && $this->actorCanCreate($t)
            ));

            $result[$cat->value] = [
                'label' => $cat->label(),
                'badge' => $cat->badgeClass(),
                'types' => $types,
            ];
        }
        return $result;
    }

    private function actorCanCreate(TreasuryTransactionType $type): bool
    {
        $user = Auth::user();
        if ($user->isOwner()) return true;

        return match ($type->category()) {
            TreasuryTransactionCategory::InternalTransfer,
            TreasuryTransactionCategory::PettyCash        => $user->can('treasury.create_transfer'),
            TreasuryTransactionCategory::Equity           => $user->can('treasury.create_equity'),
            TreasuryTransactionCategory::Adjustment       => $user->can('treasury.create_adjustment'),
            TreasuryTransactionCategory::BankFinance      => $user->can('treasury.create_bank_finance'),
        };
    }

    public function save(CreateTreasuryTransactionAction $action): void
    {
        if (! $this->transactionType) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select a transaction type.']);
            return;
        }

        $this->validate([
            'transactionType'  => 'required|string',
            'amount'           => 'required|numeric|min:0.01',
            'transactionDate'  => 'required|date',
            'description'      => 'required|string|min:3|max:500',
            'feeAmount'        => 'nullable|numeric|min:0',
            'attachment'       => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        $attachmentPath = $this->attachment
            ? $this->attachment->store(
                "shops/" . Auth::user()->shop_id . "/treasury",
                'public'
            )
            : null;

        $shop = Auth::user()->shop()->withoutGlobalScopes()
            ->findOrFail(Auth::user()->shop_id);

        try {
            $txn = $action->execute($shop, [
                'transaction_type'        => $this->transactionType,
                'branch_id'               => $this->branchId,
                'from_payment_account_id' => $this->fromAccountId ?: null,
                'to_payment_account_id'   => $this->toAccountId ?: null,
                'amount'                  => (float) $this->amount,
                'fee_amount'              => (float) ($this->feeAmount ?: 0),
                'transaction_date'        => $this->transactionDate,
                'value_date'              => $this->valueDate ?: null,
                'description'             => $this->description,
                'reference_number'        => $this->referenceNumber ?: null,
                'third_party_name'        => $this->thirdPartyName ?: null,
                'third_party_reference'   => $this->thirdPartyRef ?: null,
                'notes'                   => $this->notes ?: null,
                'attachments'             => $attachmentPath ? [$attachmentPath] : null,
            ], Auth::user());

            $statusMsg = match ($txn->status) {
                \App\Enums\TreasuryTransactionStatus::Completed
                    => "✓ {$txn->transaction_number} posted to ledger.",
                \App\Enums\TreasuryTransactionStatus::PendingApproval
                    => "⏳ {$txn->transaction_number} submitted for Owner approval.",
                default
                    => "{$txn->transaction_number} saved.",
            };

            $this->dispatch('notify', ['type' => 'success', 'message' => $statusMsg]);
            $this->redirect(route('treasury.show', $txn), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.treasury.transaction-form');
    }
}