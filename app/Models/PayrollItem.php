<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payroll_run_id', 'user_id', 'shop_id',
    'base_salary', 'house_allowance', 'transport_allowance', 'other_allowance',
    'gross_salary', 'bonus', 'advance_deduction', 'other_deduction',
    'total_deductions', 'net_salary',
    'advance_id', 'payment_account_id', 'notes', 'is_paid',
])]
class PayrollItem extends Model
{
    protected function casts(): array
    {
        return [
            'base_salary'      => 'decimal:2',
            'gross_salary'     => 'decimal:2',
            'net_salary'       => 'decimal:2',
            'bonus'            => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'other_deduction'  => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'is_paid'          => 'boolean',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function advance(): BelongsTo
    {
        return $this->belongsTo(SalaryAdvance::class, 'advance_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }
}