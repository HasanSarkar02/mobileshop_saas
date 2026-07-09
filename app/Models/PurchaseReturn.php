<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'branch_id', 'purchase_id', 'supplier_id',
    'return_number', 'total_amount', 'return_date',
    'return_reason', 'notes', 'settlement_type',
    'refund_account_id', 'journal_entry_id', 'created_by',
])]
class PurchaseReturn extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'return_date'  => 'date',
        ];
    }

    public function supplier(): BelongsTo    { return $this->belongsTo(Supplier::class); }
    public function purchase(): BelongsTo    { return $this->belongsTo(Purchase::class); }
    public function items(): HasMany         { return $this->hasMany(PurchaseReturnItem::class); }
    public function refundAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class, 'refund_account_id'); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
}