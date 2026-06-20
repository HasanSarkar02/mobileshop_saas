<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($shopId = TenantContext::shopId()) {
            $builder->where($model->getTable().'.shop_id', $shopId);
        }
    }
}