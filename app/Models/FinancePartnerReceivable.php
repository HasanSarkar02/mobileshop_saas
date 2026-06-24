<?php

namespace App\Models;

use App\Enums\FPReceivableStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'sale_id', 'finance_partner_id', 'total_amount', 'settled_amount',
    'status', 'partner_reference', 'expected_settlement_date', 'notes',
])]
class FinancePartnerReceivable extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'                   => FPReceivableStatus::class,
            'total_amount'             => 'decimal:2',
            'settled_amount'           => 'decimal:2',
            'expected_settlement_date' => 'date',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function financePartner(): BelongsTo { return $this->belongsTo(FinancePartner::class); }

    public function pendingAmount(): float
    {
        return (float) $this->total_amount - (float) $this->settled_amount;
    }
}