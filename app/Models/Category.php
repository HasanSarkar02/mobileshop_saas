<?php

namespace App\Models;

use App\Enums\ProductTrackingType;
use App\Models\Concerns\BelongsToShopOrGlobal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_id', 'parent_id', 'name', 'default_tracking_type', 'is_active'])]
class Category extends Model
{
    use BelongsToShopOrGlobal;

    protected function casts(): array
    {
        return [
            'default_tracking_type' => ProductTrackingType::class,
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}