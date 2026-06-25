<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'shop_id', 'branch_id', 'sale_number', 'customer_id', 'cashier_id', 'status',
    'subtotal', 'order_discount_type', 'order_discount_value',
    'item_discount_amount', 'order_discount_amount', 'total_discount_amount',
    'vat_amount', 'grand_total', 'total_cost', 'gross_profit', 'due_collection_amount',
    'notes', 'created_by', 'voided_by', 'void_reason', 'void_journal_entry_id',
    'confirmed_at', 'voided_at', 'return_processed',
])]
class Sale extends Model
{
    use HasFactory, BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'        => SaleStatus::class,
            'subtotal'      => 'decimal:2',
            'grand_total'   => 'decimal:2',
            'total_cost'    => 'decimal:2',
            'gross_profit'  => 'decimal:2',
            'vat_amount'    => 'decimal:2',
            'total_discount_amount' => 'decimal:2',
            'due_collection_amount' => 'decimal:2',
            'confirmed_at'  => 'datetime',
            'voided_at'     => 'datetime',
            'return_processed' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function financePartnerReceivable(): HasOne
    {
        return $this->hasOne(FinancePartnerReceivable::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(\App\Models\CreditNote::class, 'original_sale_id');
    }

    /**
     * A sale is returnable if it is confirmed AND no completed credit note
     * has already been issued for it. Voided sales cannot be returned.
     */
    public function isReturnable(): bool
    {
        if ($this->status !== SaleStatus::Confirmed) {
            return false;
        }

        if ($this->return_processed) {
            return false;
        }

        $lockDate = $this->shop?->books_locked_through;
        if ($lockDate && $this->confirmed_at?->lte($lockDate)) {
            return false;
        }

        return true;
    }

    /**
     * Voidable: confirmed, not returned, not locked.
     * Deliberately separate from isReturnable().
     */
    public function isVoidable(): bool
    {
        if ($this->status !== SaleStatus::Confirmed) {
            return false;
        }

        if ($this->return_processed) {
            return false;
        }

        $lockDate = $this->shop?->books_locked_through;
        return !($lockDate && $this->confirmed_at?->lte($lockDate));
    }

    public function paymentSummary(): string
    {
        return $this->payments
            ->groupBy('payment_type')
            ->map(fn ($p, $type) => ucfirst($type) . ': ৳' . number_format($p->sum('amount'), 2))
            ->implode(' | ');
    }
}