<?php
namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id','branch_id','product_variant_id','product_unit_id',
    'adjustment_type','quantity','unit_cost','total_cost',
    'reason','notes','journal_entry_id','created_by',
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
        ];
    }

    public function variant(): BelongsTo      { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function productUnit(): BelongsTo  { return $this->belongsTo(ProductUnit::class); }
    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
}