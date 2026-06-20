<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'subscription_plan_id', 'amount', 'currency', 'gateway',
    'gateway_transaction_id', 'status', 'period_start', 'period_end',
    'gateway_response', 'paid_at',
])]
class SubscriptionPayment extends Model
{
    // Deliberately NOT using BelongsToShop — this is platform-level billing,
    // not the shop's own business accounting. Super Admin needs to see every
    // shop's payments; scoping it would hide that from the one person who needs it.

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}