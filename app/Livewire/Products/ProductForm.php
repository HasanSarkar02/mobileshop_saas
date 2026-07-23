<?php

namespace App\Livewire\Products;

use App\Enums\ProductTrackingType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Product')]
class ProductForm extends Component
{
    use \App\Traits\HasAuthorization;
    public ?Product $product = null;

    // Product fields
    public string $name = '';
    public int $brandId = 0;
    public int $categoryId = 0;
    public string $trackingType = 'non_serialized';
    public string $description = '';

    // Variants — each: [id, attributes_label, sku, selling_price, is_active, _destroy]
    public array $variants = [];

    public bool $showBrandForm = false;
    public string $newBrandName = '';
    public bool $showCategoryForm = false;
    public string $newCategoryName = '';

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->requirePermission('inventory.edit');
        } else {
            $this->requirePermission('inventory.create');
        }
        if ($product && $product->exists) {
            $this->product      = $product->load('variants');
            $this->name         = $product->name;
            $this->brandId      = $product->brand_id ?? 0;
            $this->categoryId   = $product->category_id ?? 0;
            $this->trackingType = $product->tracking_type->value;
            $this->description  = $product->description ?? '';

            $this->variants = $product->variants->map(fn($v) => [
                'id'               => $v->id,
                'attributes_label' => $v->attributes_label ?? '',
                'sku'              => $v->sku,
                'barcode'          => $v->barcode,
                'selling_price'    => $v->selling_price,
                'min_stock_level'  => $v->min_stock_level,
                'is_active'        => $v->is_active,
                '_destroy'         => false,
            ])->toArray();
        } else {
            $this->addVariant();
        }
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id'               => null,
            'attributes_label' => '',
            'sku'              => '',
            'barcode'          => null,
            'selling_price'    => '',
            'min_stock_level'  => null,
            'is_active'        => true,
            '_destroy'         => false,
        ];
    }

    public function removeVariant(int $idx): void
    {
        $activeCount = collect($this->variants)->filter(fn($v) => ! $v['_destroy'])->count();

        if ($activeCount <= 1) {
            $this->dispatch('notify', type: 'error', message: 'A product must have at least one variant.');
            return;
        }

        if (! empty($this->variants[$idx]['id'])) {
            // Existing variant — mark for deletion, not removed from array
            // (we need the id to delete it in save())
            $this->variants[$idx]['_destroy'] = true;
        } else {
            // New (unsaved) variant — remove from array entirely
            array_splice($this->variants, $idx, 1);
        }
    }

    public function updatedTrackingType(): void
    {
        // When tracking type changes, re-suggest SKUs
        foreach ($this->variants as $idx => $_) {
            if (empty($this->variants[$idx]['sku'])) {
                $this->variants[$idx]['sku'] = $this->suggestSku($idx);
            }
        }
    }

    public function updatedName(): void
    {
        // Auto-suggest SKU for any empty slot when name is set
        foreach ($this->variants as $idx => $v) {
            if (empty($v['sku']) && empty($v['id'])) {
                $this->variants[$idx]['sku'] = $this->suggestSku($idx);
            }
        }
    }

    private function suggestSku(int $variantIndex): string
    {
        $base = Str::upper(Str::slug(Str::limit($this->name, 8, ''), '-'));
        $suffix = str_pad((string) ($variantIndex + 1), 2, '0', STR_PAD_LEFT);
        return $base ? "{$base}-{$suffix}" : "SKU-{$suffix}";
    }

    public function quickAddBrand(): void
    {
        $this->validate(['newBrandName' => 'required|string|max:100']);

        $brand = Brand::firstOrCreate(
            ['name' => trim($this->newBrandName), 'shop_id' => Auth::user()->shop_id],
            ['is_active' => true]
        );

        $this->brandId = $brand->id;
        $this->newBrandName = '';
        $this->showBrandForm = false;
        $this->dispatch('notify', type: 'success', message: "Brand \"{$brand->name}\" added.");
    }

    public function quickAddCategory(): void
    {
        $this->validate(['newCategoryName' => 'required|string|max:100']);

        $category = Category::firstOrCreate(
            ['name' => trim($this->newCategoryName), 'shop_id' => Auth::user()->shop_id],
            ['default_tracking_type' => $this->trackingType, 'is_active' => true]
        );

        $this->categoryId = $category->id;
        $this->newCategoryName = '';
        $this->showCategoryForm = false;
        $this->dispatch('notify', type: 'success', message: "Category \"{$category->name}\" added.");
    }

    public function save(): void
    {
        $this->validate([
            'name'                        => 'required|string|max:255',
            'trackingType'                => 'required|in:serialized,non_serialized',
            'variants'                    => 'required|array|min:1',
            'variants.*.sku'              => 'required|string|max:100',
            'variants.*.selling_price'    => 'required|numeric|min:0',
            'variants.*.attributes_label' => 'nullable|string|max:255',
        ], [
            'variants.*.sku.required'           => 'Every variant needs a SKU.',
            'variants.*.selling_price.required' => 'Every variant needs a selling price.',
            'variants.*.selling_price.min'      => 'Selling price cannot be negative.',
        ]);

        // Ensure no duplicate SKUs within this form
        $skus = collect($this->variants)
            ->filter(fn($v) => ! $v['_destroy'])
            ->pluck('sku')
            ->map(fn($s) => strtoupper(trim($s)));

        if ($skus->count() !== $skus->unique()->count()) {
            $this->addError('variants', 'Each variant must have a unique SKU.');
            return;
        }

        DB::transaction(function () {
            $shopId = Auth::user()->shop_id;

            $productData = [
                'shop_id'       => $shopId,
                'brand_id'      => $this->brandId ?: null,
                'category_id'   => $this->categoryId ?: null,
                'name'          => $this->name,
                'tracking_type' => $this->trackingType,
                'description'   => $this->description ?: null,
                'is_active'     => true,
            ];

            if ($this->product?->exists) {
                $this->product->update($productData);
                $product = $this->product;
            } else {
                $product = Product::create($productData);
            }

            foreach ($this->variants as $variantData) {
                if ($variantData['_destroy'] && ! empty($variantData['id'])) {
                    // Check if this variant has any stock/units first — if so, only deactivate
                    $variant = ProductVariant::find($variantData['id']);
                    if ($variant) {
                        if ($variant->units()->exists() || $variant->branchStocks()->where('quantity', '>', 0)->exists()) {
                            $variant->update(['is_active' => false]);
                        } else {
                            $variant->delete();
                        }
                    }
                    continue;
                }

                $barcode = $this->normalizeBarcode($variantData['barcode'] ?? null);

                $vData = [
                    'shop_id'          => $shopId,
                    'product_id'       => $product->id,
                    'attributes_label' => $variantData['attributes_label'] ?: null,
                    'sku'              => strtoupper(trim($variantData['sku'])),
                    'barcode'          => $barcode,
                    'selling_price'    => (float) $variantData['selling_price'],
                    'min_stock_level'  => isset($variantData['min_stock_level']) && $variantData['min_stock_level'] !== '' 
                          ? (int) $variantData['min_stock_level'] : null,
                    'is_active'        => $variantData['is_active'],
                ];

                if ($barcode !== null) {
                    $dupe = ProductVariant::withTrashed()
                        ->where('shop_id', $shopId)
                        ->where('barcode', $barcode)
                        ->when(! empty($variantData['id']), fn ($q) => $q->where('id', '!=', $variantData['id']))
                        ->exists();

                    if ($dupe) {
                        throw new \RuntimeException("Barcode \"{$barcode}\" is already assigned to another product in this shop.");
                    }
                }

                // Real guarantee — DB unique constraint, caught for a friendly race-condition message
                try {
                    if (! empty($variantData['id'])) {
                        ProductVariant::where('id', $variantData['id'])->where('shop_id', $shopId)->update($vData);
                    } else {
                        $existingSku = ProductVariant::withTrashed()
                            ->where('shop_id', $shopId)->where('sku', $vData['sku'])->first();
                        if ($existingSku) {
                            throw new \RuntimeException("SKU \"{$vData['sku']}\" is already used. Please choose a different one.");
                        }
                        ProductVariant::create($vData);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    if ((int) $e->getCode() === 23000) {
                        throw new \RuntimeException(
                            "SKU or barcode was just taken by another request (possibly a concurrent save). Please refresh and try again."
                        );
                    }
                    throw $e;
                }
            }
        });

        $message = $this->product?->exists
            ? "Product \"{$this->name}\" updated."
            : "Product \"{$this->name}\" created.";

        $this->dispatch('notify', type: 'success', message: $message);
        $this->redirect(route('products.index'), navigate: true);
    }

    private function normalizeBarcode(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    public function render()
    {
        $shopId = Auth::user()->shop_id;

        $brands = Brand::where(fn($q) =>
            $q->whereNull('shop_id')->orWhere('shop_id', $shopId)
        )->orderBy('name')->get();

        $categories = Category::where(fn($q) =>
            $q->whereNull('shop_id')->orWhere('shop_id', $shopId)
        )->orderBy('name')->get();

        return view('livewire.products.product-form', compact('brands', 'categories'));
    }

    public function toggleBrandForm(): void
    {
        $this->showBrandForm = ! $this->showBrandForm;
    }

    public function toggleCategoryForm(): void
    {
        $this->showCategoryForm = ! $this->showCategoryForm;
    }
}