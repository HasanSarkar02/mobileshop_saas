<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'product_variant_id', 'part_description', 'quantity', 'unit_cost', 'line_total', 'from_inventory'])]
class ServiceTicketPart extends Model
{
    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2', 'line_total' => 'decimal:2', 'from_inventory' => 'boolean'];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(ServiceTicket::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}