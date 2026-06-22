<?php

namespace App\Models;

use App\Enums\CustomerTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'shop_id', 'customer_id', 'transaction_type', 'amount',
    'direction', 'running_balance', 'reference_type', 'reference_id',
    'notes', 'created_by',
])]
class CustomerTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'transaction_type' => CustomerTransactionType::class,
            'amount'           => 'decimal:2',
            'running_balance'  => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}