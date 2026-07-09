<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_return_id', 'purchase_line_item_id', 'product_variant_id',
    'product_unit_id', 'quantity', 'unit_cost', 'line_total',
    'condition', 'notes',
])]
class PurchaseReturnItem extends Model
{
    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function purchaseReturn(): BelongsTo  { return $this->belongsTo(PurchaseReturn::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function unit(): BelongsTo    { return $this->belongsTo(ProductUnit::class, 'product_unit_id'); }
}