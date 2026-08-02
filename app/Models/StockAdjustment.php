<?php
namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id','branch_id','product_variant_id','product_unit_id',
    'adjustment_type','quantity','unit_cost','total_cost',
    'reason','notes','journal_entry_id','created_by','reference_type',
    'reference_id','held_for_name','held_for_phone','hold_expires_at',
    'reversal_of_id','reversed_by_id','reversed_at','reversal_reason',
])]
class StockAdjustment extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:2',
            'unit_cost'  => 'decimal:2',
            'total_cost' => 'decimal:2',
            'hold_expires_at'  => 'datetime',
            'reversed_at'      => 'datetime',
        ];
    }

    public function variant(): BelongsTo      { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function productUnit(): BelongsTo  { return $this->belongsTo(ProductUnit::class); }
    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo { return $this->morphTo(); }

    /** The original entry this row reverses (present only on a reversal row) */
    public function reversalOf(): BelongsTo { return $this->belongsTo(StockAdjustment::class, 'reversal_of_id'); }

    /** The reversal row that corrected this entry (present only on a reversed original) */
    public function reversedBy(): BelongsTo { return $this->belongsTo(StockAdjustment::class, 'reversed_by_id'); }

    /** All ProductUnits (IMEIs) created as part of this batch — opening stock serialized entries only */
    public function productUnits(): HasMany { return $this->hasMany(ProductUnit::class, 'stock_adjustment_id'); }

    public function isHoldExpired(): bool
    {
        return $this->hold_expires_at !== null && $this->hold_expires_at->isPast();
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }
}