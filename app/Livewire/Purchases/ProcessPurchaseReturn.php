<?php

namespace App\Livewire\Purchases;

use App\Actions\ProcessPurchaseReturnAction;
use App\Models\PaymentAccount;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Process Purchase Return')]
class ProcessPurchaseReturn extends Component
{
    use \App\Traits\HasAuthorization;

    public Purchase $purchase;

    public string $returnDate      = '';
    public string $returnReason    = '';
    public string $settlementType  = 'credit_note';
    public int    $refundAccountId = 0;
    public string $notes           = '';

    // Return items — one per returnable purchase line
    // ['purchase_line_item_id', 'product_variant_id', 'product_name', 'sku',
    //  'original_qty', 'max_qty', 'unit_cost', 'selected', 'quantity', 'condition']
    public array $returnItems = [];

    public function mount(Purchase $purchase): void
    {
        $this->requirePermission('purchases.manage');

        if ($purchase->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $this->purchase    = $purchase->load(['lineItems.variant.product', 'lineItems.units', 'supplier']);
        $this->returnDate  = now()->format('Y-m-d');

        $this->returnItems = $purchase->lineItems->map(fn ($line) => [
            'purchase_line_item_id' => $line->id,
            'product_variant_id'    => $line->product_variant_id,
            'product_name'          => $line->variant?->product?->name
                                    ?? 'Product #' . $line->product_variant_id,
            'sku'                   => $line->variant?->sku ?? '',
            'original_qty'          => $line->quantity,
            'unit_cost'             => (float) $line->unit_cost,
            'selected'              => false,
            'quantity'              => $line->quantity,
            'condition'             => 'good',
            // For serialized items, show IMEI picker
            'product_unit_id'       => null,
            'available_units'       => $line->units
                ->where('status', 'in_stock')
                ->pluck('serial_number', 'id')
                ->toArray(),
        ])->toArray();
    }

    #[Computed]
    public function totalReturnAmount(): float
    {
        return collect($this->returnItems)
            ->filter(fn ($i) => $i['selected'])
            ->sum(fn ($i) => (float) $i['unit_cost'] * (int) $i['quantity']);
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)->get();
    }

    public function save(ProcessPurchaseReturnAction $action): void
    {
        $selected = collect($this->returnItems)->filter(fn ($i) => $i['selected']);

        if ($selected->isEmpty()) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'Please select at least one item to return.']);
            return;
        }

        $this->validate([
            'returnDate'   => 'required|date',
            'returnReason' => 'required|string|min:5',
            'settlementType' => 'required|in:credit_note,cash_refund',
            'refundAccountId' => [
                'required_if:settlementType,cash_refund',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($this->settlementType === 'cash_refund' && (int) $value < 1) {
                        $fail('Please select a refund account for cash refund.');
                    }
                },
            ],
        ]);

        try {
            $return = $action->execute($this->purchase, [
                'return_date'      => $this->returnDate,
                'return_reason'    => $this->returnReason,
                'settlement_type'  => $this->settlementType,
                'refund_account_id'=> $this->refundAccountId ?: null,
                'notes'            => $this->notes ?: null,
                'items'            => $selected->map(fn ($i) => [
                    'purchase_line_item_id' => $i['purchase_line_item_id'],
                    'product_variant_id'    => $i['product_variant_id'],
                    'product_unit_id'       => $i['product_unit_id'] ?: null,
                    'quantity'              => (int) $i['quantity'],
                    'unit_cost'             => (float) $i['unit_cost'],
                    'condition'             => $i['condition'],
                ])->values()->toArray(),
            ], Auth::user());

            $this->dispatch('notify', ['type' => 'success',
                'message' => "Return {$return->return_number} processed successfully."]);

            $this->redirect(route('purchases.show', $this->purchase), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.purchases.process-purchase-return');
    }
}