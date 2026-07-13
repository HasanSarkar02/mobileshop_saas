<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'salary_structure_id', 'component_id', 'calculation_type',
    'value', 'percentage_of', 'formula', 'is_active',
])]
class EmployeeSalaryComponent extends Model
{
    protected function casts(): array
    {
        return [
            'value'     => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryStructure::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}