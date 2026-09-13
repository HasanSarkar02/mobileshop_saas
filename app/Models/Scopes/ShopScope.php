<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;

class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($shopId = TenantContext::shopId()) {
            $builder->where($model->getTable().'.shop_id', $shopId);
            return;
        }

        Log::warning('ShopScope hit with null tenant context — returning zero rows.', [
            'model' => $model::class,
        ]);
        $builder->whereRaw('1 = 0');
    }
}