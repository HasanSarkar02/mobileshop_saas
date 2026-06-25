<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'finance_partner_id', 'payment_account_id',
    'reference_number', 'gross_amount', 'fee_deducted', 'net_amount',
    'allocated_amount', 'settlement_date', 'notes', 'created_by',
])]
class FinancePartnerSettlement extends Model
{
    use HasFactory, BelongsToShop;

    protected function casts(): array
    {
        return [
            'gross_amount'     => 'decimal:2',
            'fee_deducted'     => 'decimal:2',
            'net_amount'       => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'settlement_date'  => 'date',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(FinancePartner::class, 'finance_partner_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FinancePartnerSettlementAllocation::class, 'settlement_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unallocatedAmount(): float
    {
        return max(0, (float) $this->net_amount - (float) $this->allocated_amount);
    }
}