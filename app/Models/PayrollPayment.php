<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'payroll_run_id', 'slip_id', 'payment_number',
    'payment_account_id', 'payment_method', 'amount', 'payment_date',
    'reference_number', 'notes', 'status',
    'journal_entry_id', 'reversal_journal_entry_id',
    'reversed_by', 'reversed_at', 'reversal_reason', 'created_by',
])]
class PayrollPayment extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'payment_date' => 'date',
            'reversed_at'  => 'datetime',
        ];
    }

    public function slip(): BelongsTo          { return $this->belongsTo(PayrollSlip::class); }
    public function payrollRun(): BelongsTo    { return $this->belongsTo(PayrollRun::class); }
    public function paymentAccount(): BelongsTo{ return $this->belongsTo(PaymentAccount::class); }
    public function journalEntry(): BelongsTo  { return $this->belongsTo(JournalEntry::class); }
    public function reversedBy(): BelongsTo    { return $this->belongsTo(User::class, 'reversed_by'); }
    public function createdBy(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }
}