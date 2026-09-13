<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        if (! $shopId = TenantContext::shopId()) {
            // Fail closed: without a tenant context this query must return
            // ZERO rows, never every tenant's data. (A null *branch* with a
            // valid shop is still legitimate — Owner sees all branches.)
            Log::warning('BranchScope hit with null tenant context — returning zero rows.', [
                'model' => $model::class,
            ]);
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where("{$table}.shop_id", $shopId);

        // Null branch context = "all branches this user can access" (Owner).
        // A set branch_id restricts the query to exactly one branch.
        if ($branchId = TenantContext::branchId()) {
            $builder->where("{$table}.branch_id", $branchId);
        }
    }
}