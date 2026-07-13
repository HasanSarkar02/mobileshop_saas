<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'slip_id', 'component_id', 'component_name', 'component_code',
    'component_type', 'is_taxable', 'sequence',
    'calculation_type', 'calculation_basis', 'formula_used', 'computed_value',
])]
class PayrollSlipComponent extends Model
{
    protected function casts(): array
    {
        return [
            'computed_value' => 'decimal:2',
            'is_taxable'     => 'boolean',
        ];
    }

    public function slip(): BelongsTo      { return $this->belongsTo(PayrollSlip::class); }
    public function component(): BelongsTo { return $this->belongsTo(PayrollComponent::class); }
}