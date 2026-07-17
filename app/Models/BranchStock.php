<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_id', 'branch_id', 'product_variant_id', 'quantity', 'average_cost'])]
class BranchStock extends Model
{
    use BelongsToBranch;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'average_cost' => 'decimal:4',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Quantity actually available for sale.
     * quantity - reserved_quantity (damaged already subtracted when marked)
     */
    public function getAvailableQuantityAttribute(): float
    {
        return max(0, (float)$this->quantity - (float)$this->reserved_quantity);
    }

    /**
     * Check if available stock meets the order quantity.
     */
    public function canFulfil(float $qty): bool
    {
        return $this->available_quantity >= $qty;
    }
}