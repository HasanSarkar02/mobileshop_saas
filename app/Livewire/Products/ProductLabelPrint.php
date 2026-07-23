<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Print Barcode Labels')]
class ProductLabelPrint extends Component
{
    use \App\Traits\HasAuthorization;

    /** Selected variant IDs and how many labels to print for each. */
    public array $selections = []; // [variant_id => qty]

    public string $labelSize = 'medium'; // small | medium | large — physical label stock preset

    public function mount(): void
    {
        $this->requirePermission('inventory.view');

        // Pre-populate from query string, e.g. ?variant=42 from the
        // Product Detail page's "Print Labels" link, or ?variants=1,2,3
        // from a bulk selection on ProductList.
        $variantId = request()->query('variant');
        $variantIds = request()->query('variants');

        if ($variantId) {
            $this->selections[(int) $variantId] = 1;
        } elseif ($variantIds) {
            foreach (explode(',', $variantIds) as $id) {
                $this->selections[(int) $id] = 1;
            }
        }
    }

    public function addVariant(int $variantId): void
    {
        if (! isset($this->selections[$variantId])) {
            $this->selections[$variantId] = 1;
        }
    }

    public function removeVariant(int $variantId): void
    {
        unset($this->selections[$variantId]);
    }

    public function render()
    {
        $shopId = Auth::user()->shop_id;

        $variants = ProductVariant::with('product.brand')
            ->whereIn('id', array_keys($this->selections))
            ->whereHas('product', fn ($q) => $q->where('shop_id', $shopId))
            ->where('is_active', true)
            ->get()
            ->filter(fn ($v) => $v->product->tracking_type->value === 'non_serialized' && ! empty($v->barcode));
            // Serialized variants are silently excluded here — per Issue #7,
            // a shared model barcode is never the correct label for an
            // individually-serialized unit. If a shop needs unit-level IMEI
            // labels, that is a deliberately separate feature (see note
            // below), not this bulk product-barcode printer.

        return view('livewire.products.product-label-print', compact('variants'));
    }
}