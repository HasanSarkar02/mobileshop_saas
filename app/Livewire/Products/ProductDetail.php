<?php

namespace App\Livewire\Products;

use App\Enums\UnitStatus;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\ProductUnit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Product Detail')]
class ProductDetail extends Component
{
    use WithPagination;

    public Product $product;

    #[Url]
    public int $selectedVariantId = 0;

    #[Url]
    public string $unitStatus = 'in_stock';

    #[Url]
    public string $imeiSearch = '';

    public function mount(Product $product): void
    {
        $this->product = $product->load(['brand', 'category', 'variants' => fn($q) => $q->withCount([
            'units as in_stock_count' => fn($q) => $q->where('status', UnitStatus::InStock)->where('is_archived', false),
            'units as sold_count' => fn($q) => $q->where('status', UnitStatus::Sold),
        ])]);

        $this->selectedVariantId = $this->product->variants->first()?->id ?? 0;
    }

    public function render()
    {
        $variant = $this->selectedVariantId
            ? $this->product->variants->find($this->selectedVariantId)
            : null;

        // Serialized: show individual units
        $units = null;
        if ($this->product->tracking_type->value === 'serialized' && $variant) {
            $units = ProductUnit::withoutGlobalScopes()
                ->where('shop_id', $this->product->shop_id)
                ->where('product_variant_id', $variant->id)
                ->where('is_archived', false)
                ->when($this->unitStatus, fn($q) => $q->where('status', $this->unitStatus))
                ->when($this->imeiSearch, fn($q) =>
                    $q->where('serial_number', 'like', "%{$this->imeiSearch}%")
                      ->orWhere('secondary_serial_number', 'like', "%{$this->imeiSearch}%")
                )
                ->with('branch')
                ->latest()
                ->paginate(20);
        }

        // Non-serialized: show branch stock
        $branchStocks = null;
        if ($this->product->tracking_type->value === 'non_serialized' && $variant) {
            $branchStocks = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $this->product->shop_id)
                ->where('product_variant_id', $variant->id)
                ->with('branch')
                ->get();
        }

        // Summary counts per variant (serialized only)
        $statusCounts = [];
        if ($this->product->tracking_type->value === 'serialized' && $variant) {
            $statusCounts = ProductUnit::withoutGlobalScopes()
                ->where('product_variant_id', $variant->id)
                ->where('is_archived', false)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        }

        return view('livewire.products.product-detail', compact('variant', 'units', 'branchStocks', 'statusCounts'));
    }
}