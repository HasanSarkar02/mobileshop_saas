<?php

namespace App\Models;

use App\Enums\PayrollComponentType;
use App\Enums\ComponentCalculationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'shop_id', 'name', 'code', 'component_type', 'calculation_type',
    'default_value', 'percentage_of', 'formula', 'is_taxable',
    'is_recurring', 'is_system', 'affects_gross', 'sequence',
    'gl_account_code', 'description', 'is_active',
])]
class PayrollComponent extends Model
{
    protected function casts(): array
    {
        return [
            'component_type'   => PayrollComponentType::class,
            'calculation_type' => ComponentCalculationType::class,
            'default_value'    => 'decimal:4',
            'is_taxable'       => 'boolean',
            'is_recurring'     => 'boolean',
            'is_system'        => 'boolean',
            'affects_gross'    => 'boolean',
            'is_active'        => 'boolean',
        ];
    }

    // GlobalOrShopScope — null shop_id = global component visible to all shops
    protected static function booted(): void
    {
        static::addGlobalScope('shopOrGlobal', function (Builder $builder) {
            if (auth()->check() && auth()->user()->shop_id) {
                $builder->where(function ($q) {
                    $q->whereNull('shop_id')
                      ->orWhere('shop_id', auth()->user()->shop_id);
                });
            }
        });
    }

    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(PayrollPolicy::class, 'payroll_policy_components')
            ->withPivot(['calculation_type', 'default_value', 'percentage_of', 'formula', 'sequence']);
    }

    public function isEarning(): bool
    {
        return $this->component_type === PayrollComponentType::Earning;
    }

    public function isDeduction(): bool
    {
        return $this->component_type === PayrollComponentType::Deduction;
    }
}