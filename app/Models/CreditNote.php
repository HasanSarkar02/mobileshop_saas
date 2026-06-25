<?php

namespace App\Models;

use App\Enums\CreditNoteStatus;
use App\Enums\RefundMethod;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'branch_id', 'credit_note_number', 'original_sale_id', 'customer_id',
    'status', 'refund_method', 'items_total', 'refund_amount', 'restock_value',
    'refund_payment_account_id', 'refund_reference', 'reason', 'notes', 'created_by',
])]
class CreditNote extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'        => CreditNoteStatus::class,
            'refund_method' => RefundMethod::class,
            'items_total'   => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'restock_value' => 'decimal:2',
        ];
    }

    public function items(): HasMany { return $this->hasMany(CreditNoteItem::class); }
    public function originalSale(): BelongsTo { return $this->belongsTo(Sale::class, 'original_sale_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function refundPaymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class, 'refund_payment_account_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}