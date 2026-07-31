<?php

namespace App\Livewire\Products;

use App\Enums\ProductTrackingType;
use App\Enums\UnitStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Products')]
class ProductList extends Component
{
    use \App\Traits\HasAuthorization;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $trackingType = '';

    #[Url]
    public int $categoryId = 0;

    #[Url]
    public int $brandId = 0;

    public bool   $showAddBrand    = false;
    public bool   $showAddCategory = false;
    public string $newBrandName    = '';
    public string $newCategoryName = '';

    #[Url]
    public bool $lowStockOnly = false;

    public function updatingLowStockOnly(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->requirePermission('inventory.view');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTrackingType(): void { $this->resetPage(); }
    public function updatingCategoryId(): void { $this->resetPage(); }
    public function updatingBrandId(): void { $this->resetPage(); }

    public function toggleActive(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);
        $this->dispatch('notify', type: 'success', message: 'Product status updated.');
    }

    public function render()
    {
        $products = Product::with(['brand', 'category'])
            ->catalogOnly()
            ->when($this->search, fn ($q) =>$q->where('name', 'like', "%{$this->search}%"))
            ->when($this->trackingType, fn ($q) =>$q->where('tracking_type', $this->trackingType))
            ->when($this->categoryId, fn ($q) =>$q->where('category_id', $this->categoryId))
            ->when($this->brandId, fn ($q) =>$q->where('brand_id', $this->brandId))
            ->when($this->lowStockOnly, fn ($q) => $q->lowStock())
            ->withCount(['variants as active_variant_count' => fn ($q) => $q->where('is_active', true)])
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)
                    ->withSum('branchStocks as ns_qty', 'quantity')
                    ->withCount(['units as sr_qty' => fn ($q) =>
                        $q->where('status', UnitStatus::InStock)->where('is_archived', false)
                    ]);
            }])
            ->latest()
            ->paginate(20);

        $categories = Category::orderBy('name')->get();

        $brands = Brand::whereHas('products', fn ($q) =>
            $q->where('products.shop_id', Auth::user()->shop_id)
        )->orderBy('name')->get();

        return view('livewire.products.product-list', compact('products', 'categories', 'brands'));
    }

    public function addBrand(): void
    {
        $this->requirePermission('inventory.edit');
        $this->validate(['newBrandName' => 'required|string|max:100']);

        \App\Models\Brand::firstOrCreate(
            ['name' => $this->newBrandName, 'shop_id' => Auth::user()->shop_id],
            ['is_active' => true]
        );

        $this->showAddBrand  = false;
        $this->newBrandName  = '';
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Brand added.']);
    }

    public function addCategory(): void
    {
        $this->requirePermission('inventory.edit');
        $this->validate(['newCategoryName' => 'required|string|max:100']);

        \App\Models\Category::firstOrCreate(
            ['name' => $this->newCategoryName, 'shop_id' => Auth::user()->shop_id],
            ['is_active' => true]
        );

        $this->showAddCategory  = false;
        $this->newCategoryName  = '';
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Category added.']);
    }
}