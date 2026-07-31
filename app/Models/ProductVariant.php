<?php

namespace App\Models;

use App\Enums\ProductTrackingType;
use App\Enums\UnitStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shop_id', 'product_id', 'sku', 'attributes_label', 'attributes', 'selling_price', 'is_active','barcode',
        'min_stock_level'])]
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function effectiveLowStockThreshold(?int $globalThreshold = 3): int
    {
        return $this->min_stock_level ?? $globalThreshold;
    }

    public function scopeByBarcode($query, string $barcode, ?int $shopId = null)
    {
        return $query->where('barcode', $barcode)
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId));
    }

    public function getCurrentStockAttribute(): int
    {
        return $this->product->tracking_type === ProductTrackingType::Serialized
            ? (int) ($this->sr_qty ?? 0)
            : (int) ($this->ns_qty ?? 0);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->effectiveLowStockThreshold();
    }

    public function scopeLowStock($query, int $globalThreshold = 3)
    {
        return $query->where(function ($outer) use ($globalThreshold) {
            // Non-serialized: sum(branch_stocks.quantity) <= threshold
            $outer->where(function ($q) use ($globalThreshold) {
                $q->whereHas('product', fn ($pq) =>
                    $pq->where('tracking_type', ProductTrackingType::NonSerialized)
                )->whereRaw('(
                    select coalesce(sum(bs.quantity), 0)
                    from branch_stocks bs
                    where bs.product_variant_id = product_variants.id
                ) <= coalesce(product_variants.min_stock_level, ?)', [$globalThreshold]);
            })
            // Serialized: count(in_stock, not archived) <= threshold
            ->orWhere(function ($q) use ($globalThreshold) {
                $q->whereHas('product', fn ($pq) =>
                    $pq->where('tracking_type', ProductTrackingType::Serialized)
                )->whereRaw('(
                    select count(*)
                    from product_units pu
                    where pu.product_variant_id = product_variants.id
                    and pu.status = ?
                    and pu.is_archived = 0
                ) <= coalesce(product_variants.min_stock_level, ?)', [UnitStatus::InStock->value, $globalThreshold]);
            });
        });
    }
}