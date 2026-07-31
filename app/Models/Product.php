<?php

namespace App\Models;

use App\Enums\ProductTrackingType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['shop_id', 'brand_id', 'category_id', 'name', 'tracking_type', 'description', 'is_active', 'system_origin'])]
class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop, LogsActivity;

    public const ORIGIN_USED_PHONE_BUCKET = 'used_phone_bucket';
    protected function casts(): array
    {
        return [
            'tracking_type' => ProductTrackingType::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'brand_id', 'category_id', 'tracking_type', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('product');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeCatalogOnly($query)
    {
        return $query->whereNull('system_origin');
    }

    public function scopeSystemOrigin($query, string $origin)
    {
        return $query->where('system_origin', $origin);
    }

    public function scopeLowStock($query, int $globalThreshold = 3)
    {
        return $query
            ->catalogOnly()
            ->whereHas('variants', fn ($vq) =>
                $vq->where('is_active', true)->lowStock($globalThreshold)
            );
    }
}