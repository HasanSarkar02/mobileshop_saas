<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        if ($shopId = TenantContext::shopId()) {
            $builder->where("{$table}.shop_id", $shopId);
        }

        // Null branch context = "all branches this user can access" (Owner).
        // A set branch_id restricts the query to exactly one branch.
        if ($branchId = TenantContext::branchId()) {
            $builder->where("{$table}.branch_id", $branchId);
        }
    }
}