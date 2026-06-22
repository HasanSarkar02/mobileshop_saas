<?php

namespace App\Livewire\Products;

use App\Enums\ProductTrackingType;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Products')]
class ProductList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $trackingType = '';

    #[Url]
    public int $categoryId = 0;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTrackingType(): void { $this->resetPage(); }
    public function updatingCategoryId(): void { $this->resetPage(); }

    public function toggleActive(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);
        $this->dispatch('notify', type: 'success', message: 'Product status updated.');
    }

    public function render()
    {
        $products = Product::with(['brand', 'category', 'variants'])
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
            )
            ->when($this->trackingType, fn($q) =>
                $q->where('tracking_type', $this->trackingType)
            )
            ->when($this->categoryId, fn($q) =>
                $q->where('category_id', $this->categoryId)
            )
            ->withCount(['variants as active_variant_count' => fn($q) => $q->where('is_active', true)])
            ->latest()
            ->paginate(20);

        $categories = Category::orderBy('name')->get();

        return view('livewire.products.product-list', compact('products', 'categories'));
    }
}