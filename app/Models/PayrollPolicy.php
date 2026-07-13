<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'name', 'code', 'description',
    'employment_type', 'is_default', 'is_active',
])]
class PayrollPolicy extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ];
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(PayrollComponent::class, 'payroll_policy_components')
            ->withPivot([
                'calculation_type', 'default_value', 'percentage_of',
                'formula', 'is_required', 'sequence',
            ])
            ->orderByPivot('sequence');
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructure::class, 'policy_id');
    }
}