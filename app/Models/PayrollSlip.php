<?php

namespace App\Models;

use App\Enums\PayrollSlipStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'payroll_run_id', 'user_id',
    'employee_name', 'designation', 'department_name', 'employment_type',
    'working_days', 'days_worked', 'leaves_paid', 'leaves_unpaid',
    'absent_days', 'overtime_hours',
    'gross_earnings', 'total_deductions', 'net_payable',
    'total_paid', 'balance_payable',
    'status', 'payment_account_id', 'payment_method',
    'journal_entry_id', 'reversal_journal_entry_id', 'notes',
])]
class PayrollSlip extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'         => PayrollSlipStatus::class,
            'gross_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_payable'    => 'decimal:2',
            'total_paid'     => 'decimal:2',
            'balance_payable'=> 'decimal:2',
            'days_worked'    => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function payrollRun(): BelongsTo  { return $this->belongsTo(PayrollRun::class); }
    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function journalEntry(): BelongsTo{ return $this->belongsTo(JournalEntry::class); }

    public function components(): HasMany
    {
        return $this->hasMany(PayrollSlipComponent::class, 'slip_id')
            ->orderBy('component_type')
            ->orderBy('sequence');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(PayrollSlipComponent::class, 'slip_id')
            ->where('component_type', 'earning')
            ->orderBy('sequence');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollSlipComponent::class, 'slip_id')
            ->where('component_type', 'deduction')
            ->orderBy('sequence');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class, 'slip_id');
    }

    public function activePayments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class, 'slip_id')
            ->where('status', 'paid');
    }

    public function loanRecoveries(): HasMany
    {
        return $this->hasMany(PayrollLoanRecovery::class, 'slip_id');
    }

    public function recalculateBalance(): void
    {
        $paid = (float) $this->activePayments()->sum('amount');
        $this->update([
            'total_paid'      => $paid,
            'balance_payable' => max(0, (float) $this->net_payable - $paid),
        ]);
    }
}