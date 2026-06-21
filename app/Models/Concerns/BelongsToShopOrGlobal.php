<?php

namespace App\Models\Concerns;

use App\Models\Scopes\GlobalOrShopScope;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToShopOrGlobal
{
    public static function bootBelongsToShopOrGlobal(): void
    {
        static::addGlobalScope(new GlobalOrShopScope);

        // Deliberately no auto-fill of shop_id on creating() here — global
        // vs shop-specific must be an explicit choice by the caller, never
        // silently inferred from ambient tenant context.
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}