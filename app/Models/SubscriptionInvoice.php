<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'subscription_id', 'invoice_number', 'amount',
    'status', 'due_date', 'paid_at', 'payment_method',
    'payment_reference', 'notes',
])]
class SubscriptionInvoice extends Model
{
    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'due_date' => 'date',
            'paid_at'  => 'date',
        ];
    }

    public function shop(): BelongsTo         { return $this->belongsTo(Shop::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(ShopSubscription::class); }
}