<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Models\Shop;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (empty($model->shop_id) && TenantContext::shopId()) {
                $model->shop_id = TenantContext::shopId();
            }
            if (empty($model->branch_id) && TenantContext::branchId()) {
                $model->branch_id = TenantContext::branchId();
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}