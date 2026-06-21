<?php

namespace App\Models;

use App\Enums\UnitStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'shop_id', 'branch_id', 'product_variant_id', 'serial_number', 'secondary_serial_number',
    'cost_price', 'purchase_line_item_id', 'status', 'disposition_type', 'disposition_id',
    'manufacturer_warranty_months', 'shop_warranty_days', 'sold_at', 'is_archived',
])]
class ProductUnit extends Model
{
    use HasFactory, BelongsToBranch;

    protected function casts(): array
    {
        return [
            'status' => UnitStatus::class,
            'cost_price' => 'decimal:2',
            'sold_at' => 'datetime',
            'is_archived' => 'boolean',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function purchaseLineItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseLineItem::class);
    }

    public function disposition(): MorphTo
    {
        return $this->morphTo();
    }

    public function warrantyExpiresAt(): ?Carbon
    {
        if (! $this->sold_at || $this->manufacturer_warranty_months === 0) {
            return null;
        }

        return $this->sold_at->copy()->addMonths($this->manufacturer_warranty_months);
    }

    public function shopWarrantyExpiresAt(): ?Carbon
    {
        if (! $this->sold_at || $this->shop_warranty_days === 0) {
            return null;
        }

        return $this->sold_at->copy()->addDays($this->shop_warranty_days);
    }

    public function isUnderWarranty(): bool
    {
        $expiry = $this->warrantyExpiresAt() ?? $this->shopWarrantyExpiresAt();

        return $expiry && $expiry->isFuture();
    }
}