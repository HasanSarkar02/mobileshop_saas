<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['loan_id', 'slip_id', 'amount_recovered', 'balance_after', 'recovery_date'])]
class PayrollLoanRecovery extends Model
{
    protected function casts(): array
    {
        return [
            'amount_recovered' => 'decimal:2',
            'balance_after'    => 'decimal:2',
            'recovery_date'    => 'date',
        ];
    }

    public function loan(): BelongsTo { return $this->belongsTo(PayrollLoan::class); }
    public function slip(): BelongsTo { return $this->belongsTo(PayrollSlip::class); }
}