<?php

namespace App\Models;

use App\Enums\ReturnCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'credit_note_id', 'original_sale_item_id', 'product_variant_id', 'product_unit_id',
    'product_name', 'variant_label', 'sku', 'serial_number',
    'quantity', 'unit_price', 'unit_cost', 'line_total',
    'condition', 'restock', 'restock_branch_id', 'condition_notes',
])]
class CreditNoteItem extends Model
{
    protected function casts(): array
    {
        return [
            'condition'  => ReturnCondition::class,
            'restock'    => 'boolean',
            'unit_price' => 'decimal:2',
            'unit_cost'  => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function creditNote(): BelongsTo { return $this->belongsTo(CreditNote::class); }
    public function originalSaleItem(): BelongsTo { return $this->belongsTo(SaleItem::class, 'original_sale_item_id'); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function productUnit(): BelongsTo { return $this->belongsTo(ProductUnit::class); }
    public function restockBranch(): BelongsTo { return $this->belongsTo(Branch::class, 'restock_branch_id'); }
}