<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'plan_id', 'billing_cycle', 'price_at_signup',
    'status', 'trial_ends_at', 'current_period_start', 'current_period_end',
    'next_billing_date', 'cancelled_at', 'cancellation_reason',
    'payment_reference', 'notes',
])]
class ShopSubscription extends Model
{
    protected function casts(): array
    {
        return [
            'trial_ends_at'        => 'date',
            'current_period_start' => 'date',
            'current_period_end'   => 'date',
            'next_billing_date'    => 'date',
            'cancelled_at'         => 'date',
            'price_at_signup'      => 'decimal:2',
        ];
    }

    public function shop(): BelongsTo  { return $this->belongsTo(Shop::class); }
    public function plan(): BelongsTo  { return $this->belongsTo(SubscriptionPlan::class); }
    public function invoices(): HasMany{ return $this->hasMany(SubscriptionInvoice::class, 'subscription_id'); }

    public function isActive(): bool   { return $this->status === 'active'; }
    public function isTrial(): bool    { return $this->status === 'trial'; }
    public function isPastDue(): bool  { return $this->status === 'past_due'; }

    public function daysUntilDue(): ?int
    {
        if (! $this->next_billing_date) return null;
        return now()->diffInDays($this->next_billing_date, false);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'active'    => 'badge-green',
            'trial'     => 'badge-blue',
            'past_due'  => 'badge-red',
            'suspended' => 'badge-gray',
            'cancelled' => 'badge-gray',
            default     => 'badge-yellow',
        };
    }
}