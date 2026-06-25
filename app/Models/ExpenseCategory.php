<?php

namespace App\Models;

use App\Models\Scopes\GlobalOrShopScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_id', 'parent_id', 'name', 'gl_account_code', 'is_active', 'is_system'])]
class ExpenseCategory extends Model
{
    use \App\Models\Concerns\BelongsToShopOrGlobal;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_system' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function glAccount(): ?Account
    {
        if (! $this->gl_account_code) return null;

        return Account::withoutGlobalScopes()
            ->where('shop_id', \App\Support\TenantContext::shopId())
            ->where('code', $this->gl_account_code)
            ->first();
    }
}