<?php

namespace App\Models;

use App\Enums\PayrollRunStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'run_number', 'year', 'month', 'period_from', 'period_to',
    'branch_id', 'department_id', 'employment_type',
    'total_employees', 'total_gross_earnings', 'total_deductions',
    'total_net_payable', 'total_paid',
    'status', 'description', 'notes',
    'generated_by', 'reviewed_by', 'reviewed_at',
    'approved_by', 'approved_at',
    'cancelled_by', 'cancelled_at', 'cancellation_reason',
    'reversed_by', 'reversed_at', 'reversal_reason',
    'journal_entry_id', 'reversal_journal_entry_id',
])]
class PayrollRun extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'         => PayrollRunStatus::class,
            'period_from'    => 'date',
            'period_to'      => 'date',
            'reviewed_at'    => 'datetime',
            'approved_at'    => 'datetime',
            'cancelled_at'   => 'datetime',
            'reversed_at'    => 'datetime',
            'total_gross_earnings' => 'decimal:2',
            'total_deductions'     => 'decimal:2',
            'total_net_payable'    => 'decimal:2',
            'total_paid'           => 'decimal:2',
        ];
    }

    public function slips(): HasMany
    {
        return $this->hasMany(PayrollSlip::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo{ return $this->belongsTo(Department::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function generatedBy(): BelongsTo  { return $this->belongsTo(User::class, 'generated_by'); }
    public function approvedBy(): BelongsTo   { return $this->belongsTo(User::class, 'approved_by'); }

    public function monthName(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)
            ->format('F Y');
    }

    public function balanceRemaining(): float
    {
        return (float) $this->total_net_payable - (float) $this->total_paid;
    }

    public function recalculateTotals(): void
    {
        $this->load('slips');
        $this->update([
            'total_employees'     => $this->slips->count(),
            'total_gross_earnings'=> (float) $this->slips->sum('gross_earnings'),
            'total_deductions'    => (float) $this->slips->sum('total_deductions'),
            'total_net_payable'   => (float) $this->slips->sum('net_payable'),
            'total_paid'          => (float) $this->slips->sum('total_paid'),
        ]);
    }
}