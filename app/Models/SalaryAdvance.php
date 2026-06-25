<?php

namespace App\Models;

use App\Enums\AdvanceStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'user_id', 'amount', 'balance_remaining',
    'monthly_deduction', 'advance_date', 'purpose',
    'status', 'payment_account_id', 'created_by',
])]
class SalaryAdvance extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'            => AdvanceStatus::class,
            'amount'            => 'decimal:2',
            'balance_remaining' => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
            'advance_date'      => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }
}