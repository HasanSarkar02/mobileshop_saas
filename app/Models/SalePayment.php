<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id', 'payment_type', 'payment_account_id', 'finance_partner_id',
    'amount', 'reference_number', 'notes',
])]
class SalePayment extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function financePartner(): BelongsTo { return $this->belongsTo(FinancePartner::class); }
}