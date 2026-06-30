<?php

namespace App\Livewire\Sales;

use App\Actions\ProcessReturnAction;
use App\Enums\RefundMethod;
use App\Enums\ReturnCondition;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Process Return')]
class ProcessReturn extends Component
{
    public Sale $sale;

    public string $refundMethod             = 'original_payment';
    public int    $refundPaymentAccountId   = 0;
    public string $refundReference          = '';
    public string $returnReason             = '';
    public string $notes                    = '';

    /**
     * Each item:
     *   sale_item_id, product_name, variant_label, serial_number,
     *   original_qty, line_total, max_refund,
     *   selected, quantity, refund_amount (user editable),
     *   condition, restock, restock_branch_id, condition_notes
     */
    public array $returnItems = [];

    public function mount(Sale $sale): void
    {
        if ($sale->status->value !== 'confirmed') {
            $this->redirect(route('sales.show', $sale), navigate: true);
            return;
        }

        $sale->load(['items.variant.product', 'items.productUnit', 'payments']);

        if (! $sale->isReturnable()) {
            session()->flash('error', 'All items in this sale have already been returned.');
            $this->redirect(route('sales.show', $sale), navigate: true);
            return;
        }

        $this->sale = $sale;

        $defaultBranchId = Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)
                     ->where('is_main', true)->value('id')
            ?? 0;

        $this->returnItems = $sale->items
            ->map(function ($item) use ($defaultBranchId) {
                $availableQty = $item->quantity - $item->returned_quantity;
                $maxRefund    = $availableQty > 0
                    ? round((float) $item->line_total * $availableQty / max($item->quantity, 1), 2)
                    : 0;

                return [
                    'sale_item_id'      => $item->id,
                    'product_name'      => $item->product_name,
                    'variant_label'     => $item->variant_label,
                    'serial_number'     => $item->serial_number,
                    'original_qty'      => $item->quantity,
                    'returned_quantity' => $item->returned_quantity, // already returned previously
                    'available_qty'     => $availableQty,
                    'line_total'        => (float) $item->line_total,
                    'max_refund'        => $maxRefund,
                    'selected'          => false,
                    'quantity'          => max(1, $availableQty),
                    'refund_amount'     => number_format($maxRefund, 2, '.', ''),
                    'condition'         => ReturnCondition::Good->value,
                    'restock'           => true,
                    'restock_branch_id' => $defaultBranchId,
                    'condition_notes'   => '',
                ];
            })
            // Hide items that are already 100% returned — nothing left to return
            ->filter(fn ($i) => $i['available_qty'] > 0)
            ->values()
            ->toArray();
    }

    public function selectAll(): void
    {
        foreach ($this->returnItems as $idx => $_) {
            $this->returnItems[$idx]['selected'] = true;
        }
    }

    public function updatedReturnItems(mixed $value, string $key): void
    {
        [$idx, $field] = array_pad(explode('.', $key, 2), 2, null);
        $idx = (int) $idx;

        if ($field === 'quantity' && isset($this->returnItems[$idx])) {
            $item     = $this->returnItems[$idx];
            $availQty = (int) $item['available_qty'];
            $origQty  = (int) $item['original_qty'];
            $newQty   = max(1, min((int) $value, $availQty));
            $maxRef   = round($item['line_total'] * $newQty / max($origQty, 1), 2);

            $this->returnItems[$idx]['quantity']      = $newQty;
            $this->returnItems[$idx]['max_refund']    = $maxRef;
            $this->returnItems[$idx]['refund_amount'] = number_format($maxRef, 2, '.', '');
        }

        if ($field === 'selected' && $value) {
            if (empty($this->returnItems[$idx]['refund_amount']) || (float)$this->returnItems[$idx]['refund_amount'] === 0.0) {
                $this->returnItems[$idx]['refund_amount'] =
                    number_format($this->returnItems[$idx]['max_refund'], 2, '.', '');
            }
        }
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->get();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)
                     ->where('is_active', true)->get();
    }

    #[Computed]
    public function totalRefundAmount(): float
    {
        return collect($this->returnItems)
            ->filter(fn ($i) => $i['selected'])
            ->sum(fn ($i) => (float) ($i['refund_amount'] ?? 0));
    }

    #[Computed]
    public function paymentSummary(): array
    {
        return $this->sale->payments
            ->map(fn ($p) => [
                'method' => $p->paymentAccount?->name
                    ?? $p->financePartner?->name
                    ?? ucfirst(str_replace('_', ' ', $p->payment_type)),
                'amount' => (float) $p->amount,
                'type'   => $p->payment_type,
            ])
            ->toArray();
    }

    public function save(ProcessReturnAction $action): void
    {
        $selectedItems = collect($this->returnItems)->filter(fn ($i) => $i['selected']);

        if ($selectedItems->isEmpty()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select at least one item to return.']);
            return;
        }

        $validated = $this->validate([
            'returnReason' => 'required|string|min:3',
            'refundMethod' => 'required|in:original_payment,store_credit,exchange',
        ]);

        // Validate refund amounts
        foreach ($selectedItems as $idx => $item) {
            $refAmt = (float) ($item['refund_amount'] ?? 0);
            if ($refAmt < 0) {
                $this->addError("returnItems.{$idx}.refund_amount", "Refund cannot be negative.");
                return;
            }
            if ($refAmt > (float) $item['max_refund'] + 0.01) {
                $this->addError("returnItems.{$idx}.refund_amount",
                    "Cannot exceed ৳" . number_format($item['max_refund'], 2));
                return;
            }
        }

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $creditNote = $action->execute($this->sale, [
                'refund_method'              => $this->refundMethod,
                'reason'                     => $this->returnReason,
                'refund_payment_account_id'  => $this->refundPaymentAccountId ?: null,
                'refund_reference'           => $this->refundReference ?: null,
                'notes'                      => $this->notes ?: null,
                'items'                      => $selectedItems
                    ->map(fn ($i) => [
                        'sale_item_id'      => (int) $i['sale_item_id'],
                        'quantity'          => (int) $i['quantity'],
                        'refund_amount'     => (float) ($i['refund_amount'] ?? $i['line_total']),
                        'condition'         => $i['condition'],
                        'restock'           => (bool) $i['restock'],
                        'restock_branch_id' => (int) $i['restock_branch_id'],
                        'condition_notes'   => $i['condition_notes'] ?: null,
                    ])
                    ->values()
                    ->toArray(),
            ], Auth::user());

            $this->dispatch('notify', ['type' => 'success',
                'message' => "Return processed — {$creditNote->credit_note_number} issued."]);

            $this->redirect(route('sales.show', $this->sale), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => $e->getMessage() ?: 'An unexpected error occurred. Please try again.']);
        }
    }

    public function render()
    {
        return view('livewire.sales.process-return');
    }
}