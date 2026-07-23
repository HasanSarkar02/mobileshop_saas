<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Purchase Detail')]
class PurchaseDetail extends Component
{
    public Purchase $purchase;

    public ?int $expandedLineItemId = null;

    public function mount(Purchase $purchase): void
    {
        $this->purchase = $purchase->load([
            'supplier',
            'branch',
            'createdBy',
            'lineItems' => fn ($q) => $q->withCount('units'),
            'lineItems.variant.product.brand',
            'returns.items.variant',
        ]);
        $this->refreshLineItemsCount();
    }

    public function toggleLineItem(int $lineItemId): void
    {
        $this->expandedLineItemId = $this->expandedLineItemId === $lineItemId ? null : $lineItemId;
        $this->refreshLineItemsCount();
    }

    private function refreshLineItemsCount(): void
{
    $this->purchase->load([
        'lineItems' => fn ($q) => $q->withCount('units')
    ]);
}

    public function unitsForLineItem(int $lineItemId): \Illuminate\Support\Collection
    {
        if ($this->expandedLineItemId !== $lineItemId) {
            return collect();
        }

        return \App\Models\ProductUnit::withoutGlobalScopes()
            ->where('purchase_line_item_id', $lineItemId)
            ->orderBy('id')
            ->limit(200)
            ->get();
    
    }

    public function render()
    {
        return view('livewire.purchases.purchase-detail', ['purchase' => $this->purchase]);
    }
}