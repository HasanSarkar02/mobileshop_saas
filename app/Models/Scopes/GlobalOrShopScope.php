<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;

class GlobalOrShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($shopId = TenantContext::shopId()) {
            $table = $model->getTable();
            $builder->where(function ($query) use ($table, $shopId) {
                $query->where("{$table}.shop_id", $shopId)->orWhereNull("{$table}.shop_id");
            });
            return;
        }

        // Fail closed — see ShopScope. Global (null shop_id) rows are only
        // visible with an explicit tenant context, never without one.
        Log::warning('GlobalOrShopScope hit with null tenant context — returning zero rows.', [
            'model' => $model::class,
        ]);
        $builder->whereRaw('1 = 0');
    }
}