<?php
namespace App\Models;

use App\Enums\ServiceTicketStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'branch_id', 'ticket_number', 'customer_id',
    'customer_name', 'customer_phone',
    'device_brand', 'device_model', 'device_imei', 'device_color', 'device_condition',
    'problem_description', 'diagnosis_notes', 'internal_notes',
    'estimated_cost', 'parts_cost', 'labor_charge', 'total_charge',
    'amount_paid', 'amount_due',
    'status', 'is_warranty_service', 'product_unit_id', 'technician_id',
    'received_at', 'ready_at', 'delivered_at', 'created_by',
])]
class ServiceTicket extends Model
{
    use BelongsToBranch;

    protected function casts(): array
    {
        return [
            'status'             => ServiceTicketStatus::class,
            'is_warranty_service'=> 'boolean',
            'estimated_cost'     => 'decimal:2',
            'parts_cost'         => 'decimal:2',
            'labor_charge'       => 'decimal:2',
            'total_charge'       => 'decimal:2',
            'amount_paid'        => 'decimal:2',
            'amount_due'         => 'decimal:2',
            'received_at'        => 'datetime',
            'ready_at'           => 'datetime',
            'delivered_at'       => 'datetime',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function parts(): HasMany { return $this->hasMany(ServiceTicketPart::class, 'ticket_id'); }
    public function payments(): HasMany { return $this->hasMany(ServicePayment::class, 'ticket_id'); }
    public function technician(): BelongsTo { return $this->belongsTo(User::class, 'technician_id'); }
    public function productUnit(): BelongsTo { return $this->belongsTo(ProductUnit::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function isEditable(): bool
    {
        return ! $this->status->isTerminal();
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->total_charge - (float) $this->amount_paid);
    }

    public function recalculateTotals(): void
    {
        $partsCost = $this->parts()->sum('line_total');
        $total     = (float) $partsCost + (float) $this->labor_charge;
        $amountPaid= $this->payments()->sum('amount');

        $this->update([
            'parts_cost'  => $partsCost,
            'total_charge'=> $total,
            'amount_paid' => $amountPaid,
            'amount_due'  => max(0, $total - $amountPaid),
        ]);
    }
}