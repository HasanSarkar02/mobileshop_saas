<?php

namespace App\Models;

use App\Enums\PayrollLoanStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'user_id', 'loan_number', 'loan_type',
    'total_amount', 'outstanding_balance', 'monthly_deduction',
    'purpose', 'notes', 'status',
    'disbursement_account_id', 'disbursement_date', 'disbursement_journal_entry_id',
    'waived_amount', 'waived_by', 'waived_at', 'waiver_reason', 'waiver_journal_entry_id',
    'approved_by', 'approved_at', 'created_by',
])]
class PayrollLoan extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'               => PayrollLoanStatus::class,
            'disbursement_date'    => 'date',
            'approved_at'          => 'datetime',
            'waived_at'            => 'datetime',
            'total_amount'         => 'decimal:2',
            'outstanding_balance'  => 'decimal:2',
            'monthly_deduction'    => 'decimal:2',
            'waived_amount'        => 'decimal:2',
        ];
    }

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function disbursementAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'disbursement_account_id');
    }

    public function recoveries(): HasMany
    {
        return $this->hasMany(PayrollLoanRecovery::class, 'loan_id');
    }

    public function totalRecovered(): float
    {
        return (float) $this->recoveries()->sum('amount_recovered');
    }
}