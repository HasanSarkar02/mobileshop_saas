<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'branch_id', 'supplier_id', 'payment_account_id',
    'payment_number', 'amount', 'payment_date', 'payment_method',
    'reference_number', 'bank_name', 'notes',
    'journal_entry_id', 'created_by',
])]
class SupplierPayment extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo    { return $this->belongsTo(Supplier::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
}