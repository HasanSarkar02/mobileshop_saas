<?php

namespace App\Livewire\Products;

use App\Enums\UnitStatus;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Product Detail')]
class ProductDetail extends Component
{
    use \App\Traits\HasAuthorization;
    use WithPagination;

    public Product $product;

    #[Url]
    public int $selectedVariantId = 0;

    #[Url]
    public string $unitStatus = ''; // '' = all statuses, including sold/archived

    #[Url]
    public string $imeiSearch = '';

    public function mount(Product $product): void
    {
        $this->requirePermission('inventory.view');
        $this->product = $product->load([
            'brand',
            'category',
            'variants' => fn($q) => $q->withCount([
                'units as in_stock_count' => fn($q) =>
                    $q->where('status', UnitStatus::InStock)->where('is_archived', false),
                'units as sold_count' => fn($q) =>
                    $q->where('status', UnitStatus::Sold),
                'units as total_count',
            ])
            ->with(['Units' => function ($q) { 
            $q->where('status', UnitStatus::InStock);
        }]),
        ]);

        $this->selectedVariantId = $this->product->variants->first()?->id ?? 0;
    }

    public function updatingImeiSearch(): void { $this->resetPage(); }
    public function updatingUnitStatus(): void { $this->resetPage(); }

    public function render()
    {
        $variant = $this->selectedVariantId
            ? $this->product->variants->find($this->selectedVariantId)
            : null;

        $units        = null;
        $branchStocks = null;
        $statusCounts = [];

        if ($this->product->tracking_type->value === 'serialized' && $variant) {

            // ─── Unit query — NO is_archived filter ───────────────────────────
            // We deliberately show ALL units including sold/written-off so the
            // shop can trace the full history of every IMEI ever received.
            $units = ProductUnit::withoutGlobalScopes()
                ->where('shop_id', $this->product->shop_id)
                ->where('product_variant_id', $variant->id)
                ->when($this->unitStatus, fn($q) => $q->where('status', $this->unitStatus))
                ->when($this->imeiSearch, fn($q) =>
                    $q->where(fn($sq) =>
                        $sq->where('serial_number', 'like', "%{$this->imeiSearch}%")
                           ->orWhere('secondary_serial_number', 'like', "%{$this->imeiSearch}%")
                    )
                )
                ->with(['branch', 'purchaseLineItem.purchase.supplier'])
                ->latest()
                ->paginate(20);

            // ─── Load sale + customer info for sold units (no N+1) ─────────────
            $saleIds = collect($units->items())
                ->filter(fn($u) =>
                    $u->disposition_type === Sale::class && $u->disposition_id
                )
                ->pluck('disposition_id')
                ->filter()
                ->unique();

            if ($saleIds->isNotEmpty()) {
                $salesMap = Sale::with('customer')
                    ->withoutGlobalScopes()
                    ->whereIn('id', $saleIds)
                    ->get()
                    ->keyBy('id');

                foreach ($units->items() as $unit) {
                    if (
                        $unit->disposition_type === Sale::class
                        && $unit->disposition_id
                        && $salesMap->has($unit->disposition_id)
                    ) {
                        // Attach directly to the model instance
                        $unit->saleRecord = $salesMap->get($unit->disposition_id);
                    }
                }
            }

            // ─── Status counts — include ALL statuses (sold, archived, etc.) ──
            $statusCounts = ProductUnit::withoutGlobalScopes()
                ->where('product_variant_id', $variant->id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        }

        if ($this->product->tracking_type->value === 'non_serialized' && $variant) {
            $branchStocks = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $this->product->shop_id)
                ->where('product_variant_id', $variant->id)
                ->with('branch')
                ->get();
        }

        // Non-serialized summary — from branch_stock + sale_items
        $nonSerializedSummary = null;
        if ($this->product->tracking_type->value === 'non_serialized' && $variant) {
            $nonSerializedSummary = [
                'in_stock' => \App\Models\BranchStock::withoutGlobalScopes()
                    ->where('shop_id', $this->product->shop_id)
                    ->where('product_variant_id', $variant->id)
                    ->sum('quantity'),

                'total_sold' => \App\Models\SaleItem::whereHas('sale', fn ($q) =>
                    $q->where('shop_id', $this->product->shop_id)
                      ->where('status', 'confirmed')
                )
                ->where('product_variant_id', $variant->id)
                ->sum('quantity'),

                'total_purchased' => \App\Models\PurchaseLineItem::whereHas('purchase', fn ($q) =>
                    $q->where('shop_id', $this->product->shop_id)
                )
                ->where('product_variant_id', $variant->id)
                ->sum('quantity'),
            ];
        }

        return view('livewire.products.product-detail',
            compact('variant', 'units', 'branchStocks', 'statusCounts', 'nonSerializedSummary'));

    }
}