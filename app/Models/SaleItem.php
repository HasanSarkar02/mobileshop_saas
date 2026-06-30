<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id', 'product_variant_id', 'product_unit_id',
    'product_name', 'variant_label', 'sku', 'serial_number',
    'quantity', 'unit_price', 'original_price', 'cost_price',
    'discount_type', 'discount_value', 'discount_amount',
    'line_subtotal', 'vat_rate', 'vat_amount', 'line_total', 'profit_amount', 'returned_quantity',
])]
class SaleItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price'       => 'decimal:2',
            'original_price'   => 'decimal:2',
            'cost_price'       => 'decimal:2',
            'discount_amount'  => 'decimal:2',
            'line_subtotal'    => 'decimal:2',
            'vat_amount'       => 'decimal:2',
            'line_total'       => 'decimal:2',
            'profit_amount'    => 'decimal:2',
            'returned_quantity' => 'integer',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function productUnit(): BelongsTo { return $this->belongsTo(ProductUnit::class); }
}