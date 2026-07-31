<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'supplier_id', 'reference_number', 'amount',
    'balance_date', 'notes', 'journal_entry_id', 'created_by',
])]
class SupplierOpeningBalance extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'balance_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo     { return $this->belongsTo(Supplier::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
}