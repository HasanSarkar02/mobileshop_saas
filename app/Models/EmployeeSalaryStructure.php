<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'user_id', 'policy_id', 'department_id', 'designation',
    'employment_type', 'effective_from', 'effective_to',
    'payment_account_id', 'payment_method',
    'bank_name', 'bank_account_number', 'bank_routing_number',
    'monthly_working_days', 'weekly_off_days', 'overtime_rate',
    'notes', 'is_active', 'created_by',
])]
class EmployeeSalaryStructure extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'employment_type'  => EmploymentType::class,
            'effective_from'   => 'date',
            'effective_to'     => 'date',
            'overtime_rate'    => 'decimal:2',
            'is_active'        => 'boolean',
        ];
    }

    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function policy(): BelongsTo       { return $this->belongsTo(PayrollPolicy::class); }
    public function department(): BelongsTo   { return $this->belongsTo(Department::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }

    public function components(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'salary_structure_id');
    }

    public function activeComponents(): HasMany
    {
        return $this->components()->where('is_active', true);
    }

    // Get all components merged: employee overrides take priority over policy defaults
    public function resolvedComponents(): \Illuminate\Support\Collection
    {
        $policyComponents  = $this->policy->components()->get();
        $employeeOverrides = $this->activeComponents()
            ->with('component')->get()->keyBy('component_id');

        return $policyComponents->map(function ($comp) use ($employeeOverrides) {
            $override = $employeeOverrides->get($comp->id);
            return (object) [
                'component'        => $comp,
                'calculation_type' => $override?->calculation_type ?? $comp->pivot->calculation_type,
                'value'            => $override?->value ?? $comp->pivot->default_value,
                'percentage_of'    => $override?->percentage_of ?? $comp->pivot->percentage_of,
                'formula'          => $override?->formula ?? $comp->pivot->formula,
                'sequence'         => $comp->pivot->sequence,
            ];
        })->sortBy('sequence');
    }
}