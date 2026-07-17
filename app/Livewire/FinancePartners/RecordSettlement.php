<?php

namespace App\Livewire\FinancePartners;

use App\Actions\RecordFinancePartnerSettlementAction;
use App\Enums\FPReceivableStatus;
use App\Models\FinancePartner;
use App\Models\FinancePartnerReceivable;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Traits\HasAuthorization;

#[Layout('components.layouts.app')]
#[Title('Record Settlement')]
class RecordSettlement extends Component
{
    use HasAuthorization;
    public FinancePartner $partner;

    public int    $paymentAccountId  = 0;
    public string $referenceNumber   = '';
    public string $grossAmount       = '';
    public string $feeDeducted       = '0';
    public string $settlementDate    = '';
    public string $notes             = '';

    // Each row: receivable_id, sale_number, customer, total, pending, allocate_amount, selected
    public array $allocations = [];

    public function mount(FinancePartner $partner): void
    {
        $this->partner        = $partner;
        $this->settlementDate = now()->format('Y-m-d');

        // Load all pending receivables for this partner
        $this->loadPendingReceivables();
    }

    private function loadPendingReceivables(): void
    {
        $receivables = FinancePartnerReceivable::with('sale.customer')
            ->where('finance_partner_id', $this->partner->id)
            ->whereIn('status', [
                FPReceivableStatus::Pending->value,
                FPReceivableStatus::Partial->value,
            ])
            ->oldest()
            ->get();

        $this->allocations = $receivables->map(fn ($r) => [
            'receivable_id'  => $r->id,
            'sale_number'    => $r->sale?->sale_number,
            'customer_name'  => $r->sale?->customer?->name ?? 'Walk-in',
            'customer_phone' => $r->sale?->customer?->phone ?? '',
            'total_amount'   => (float) $r->total_amount,
            'settled_amount' => (float) $r->settled_amount,
            'pending_amount' => round((float) $r->total_amount - (float) $r->settled_amount, 2),
            'alloc_amount'   => '',   // user fills this
            'selected'       => false,
            'sale_date'      => $r->sale?->confirmed_at?->format('d M Y') ?? '—',
        ])->toArray();
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->orderBy('provider')->orderBy('name')->get();
    }

    public function selectAll(): void
    {
        foreach ($this->allocations as $idx => $_) {
            $this->allocations[$idx]['selected']    = true;
            $this->allocations[$idx]['alloc_amount'] = (string) $this->allocations[$idx]['pending_amount'];
        }
    }

    public function deselectAll(): void
    {
        foreach ($this->allocations as $idx => $_) {
            $this->allocations[$idx]['selected']    = false;
            $this->allocations[$idx]['alloc_amount'] = '';
        }
    }

    public function updatedAllocations(mixed $value, string $key): void
    {
        [$idx, $field] = array_pad(explode('.', $key, 2), 2, null);
        $idx = (int) $idx;

        // When selected → auto-fill with pending amount
        if ($field === 'selected' && $value) {
            if (empty($this->allocations[$idx]['alloc_amount'])) {
                $this->allocations[$idx]['alloc_amount'] =
                    (string) $this->allocations[$idx]['pending_amount'];
            }
        }

        // When deselected → clear amount
        if ($field === 'selected' && ! $value) {
            $this->allocations[$idx]['alloc_amount'] = '';
        }
    }

    #[Computed]
    public function netAmount(): float
    {
        return max(0, (float) $this->grossAmount - (float) $this->feeDeducted);
    }

    #[Computed]
    public function totalAllocated(): float
    {
        return collect($this->allocations)
            ->filter(fn ($a) => $a['selected'] && (float) ($a['alloc_amount'] ?? 0) > 0)
            ->sum(fn ($a) => (float) $a['alloc_amount']);
    }

    #[Computed]
    public function unallocatedBalance(): float
    {
        return max(0, $this->netAmount - $this->totalAllocated);
    }

    #[Computed]
    public function totalPendingAmount(): float
    {
        return collect($this->allocations)->sum('pending_amount');
    }

    public function save(RecordFinancePartnerSettlementAction $action): void
    {
        $this->requirePermission('emi.settle');
        $this->validate([
            'paymentAccountId' => 'required|integer|min:1',
            'grossAmount'      => 'required|numeric|min:0.01',
            'feeDeducted'      => 'required|numeric|min:0',
            'settlementDate'   => 'required|date|before_or_equal:today',
        ], [
            'paymentAccountId.min' => 'Please select a payment account.',
            'grossAmount.min'      => 'Gross amount must be greater than zero.',
        ]);

        $selectedAllocs = collect($this->allocations)
            ->filter(fn ($a) => $a['selected'] && (float) ($a['alloc_amount'] ?? 0) > 0)
            ->map(fn ($a) => [
                'receivable_id' => (int) $a['receivable_id'],
                'amount'        => (float) $a['alloc_amount'],
            ])
            ->values()
            ->toArray();

        if (empty($selectedAllocs)) {
            $this->dispatch('notify', type: 'error',
                message: 'Please select at least one receivable to allocate this settlement against.');
            return;
        }

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $settlement = $action->execute($shop, [
                'finance_partner_id' => $this->partner->id,
                'payment_account_id' => $this->paymentAccountId,
                'reference_number'   => $this->referenceNumber ?: null,
                'gross_amount'       => (float) $this->grossAmount,
                'fee_deducted'       => (float) $this->feeDeducted,
                'settlement_date'    => $this->settlementDate,
                'notes'              => $this->notes ?: null,
                'allocations'        => $selectedAllocs,
            ], Auth::user());

            $this->dispatch('notify', type: 'success',
                message: "Settlement recorded — ৳" . number_format($settlement->net_amount, 2) .
                         " received from {$this->partner->name}.");

            $this->redirect(route('finance-partners.index'), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.finance-partners.record-settlement');
    }
}