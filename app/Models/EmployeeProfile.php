<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'shop_id', 'designation', 'base_salary',
    'house_allowance', 'transport_allowance', 'other_allowance',
    'joining_date', 'nid_number', 'address',
    'emergency_contact_name', 'emergency_contact_phone',
    'salary_payment_account_id',
])]
class EmployeeProfile extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'base_salary'         => 'decimal:2',
            'house_allowance'     => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowance'     => 'decimal:2',
            'joining_date'        => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryPaymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'salary_payment_account_id');
    }

    public function advances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class, 'user_id', 'user_id');
    }

    public function grossSalary(): float
    {
        return (float) $this->base_salary
            + (float) $this->house_allowance
            + (float) $this->transport_allowance
            + (float) $this->other_allowance;
    }

    public function activeAdvanceDeduction(): float
    {
        return (float) SalaryAdvance::withoutGlobalScopes()
            ->where('user_id', $this->user_id)
            ->where('status', 'active')
            ->sum('monthly_deduction');
    }
}