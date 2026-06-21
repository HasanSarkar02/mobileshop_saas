<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class GlobalOrShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($shopId = TenantContext::shopId()) {
            $table = $model->getTable();
            $builder->where(function ($query) use ($table, $shopId) {
                $query->where("{$table}.shop_id", $shopId)->orWhereNull("{$table}.shop_id");
            });
        }
    }
}