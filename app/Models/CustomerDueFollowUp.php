<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'customer_id', 'created_by', 'followup_type', 'followup_date',
    'promised_payment_date', 'promised_amount', 'next_followup_date',
    'status', 'customer_response', 'internal_note', 'completed_at',
])]
class CustomerDueFollowUp extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'followup_type'         => FollowUpType::class,
            'status'                => FollowUpStatus::class,
            'followup_date'         => 'datetime',
            'promised_payment_date' => 'datetime',
            'promised_amount'       => 'decimal:2',
            'next_followup_date'    => 'datetime',
            'completed_at'          => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null && $this->status->isOpen();
    }
}