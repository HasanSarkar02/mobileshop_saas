<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['settlement_id', 'receivable_id', 'amount'])]
class FinancePartnerSettlementAllocation extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(FinancePartnerSettlement::class);
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(FinancePartnerReceivable::class);
    }
}