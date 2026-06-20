<?php

namespace App\Models\Concerns;

use App\Models\Scopes\ShopScope;
use App\Models\Shop;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToShop
{
    public static function bootBelongsToShop(): void
    {
        static::addGlobalScope(new ShopScope);

        static::creating(function ($model) {
            if (empty($model->shop_id) && TenantContext::shopId()) {
                $model->shop_id = TenantContext::shopId();
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}