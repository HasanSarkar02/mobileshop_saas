<?php

namespace App\Models;

use App\Enums\TreasuryTransactionCategory;
use App\Enums\TreasuryTransactionStatus;
use App\Enums\TreasuryTransactionType;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'branch_id', 'transaction_number',
    'transaction_type', 'transaction_category', 'status',
    'from_payment_account_id', 'to_payment_account_id',
    'amount', 'fee_amount', 'net_amount',
    'transaction_date', 'value_date',
    'description', 'reference_number',
    'third_party_name', 'third_party_reference',
    'approval_required', 'approval_threshold_snapshot',
    'approved_by', 'approved_at',
    'rejected_by', 'rejected_at', 'rejection_reason',
    'journal_entry_id',
    'reversal_of_id', 'reversed_by_id', 'reversed_at', 'reversal_reason',
    'attachments', 'notes', 'metadata',
    'created_by', 'updated_by',
])]
class TreasuryTransaction extends Model
{
    use BelongsToBranch;

    protected function casts(): array
    {
        return [
            'transaction_type'     => TreasuryTransactionType::class,
            'transaction_category' => TreasuryTransactionCategory::class,
            'status'               => TreasuryTransactionStatus::class,
            'amount'               => 'decimal:2',
            'fee_amount'           => 'decimal:2',
            'net_amount'           => 'decimal:2',
            'approval_threshold_snapshot' => 'decimal:2',
            'transaction_date'     => 'date',
            'value_date'           => 'date',
            'approved_at'          => 'datetime',
            'rejected_at'          => 'datetime',
            'reversed_at'          => 'datetime',
            'approval_required'    => 'boolean',
            'attachments'          => 'array',
            'metadata'             => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'from_payment_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'to_payment_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'reversal_of_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'reversed_by_id');
    }

    // ── State Queries ──────────────────────────────────────────────────────────

    public function isDraft(): bool           { return $this->status === TreasuryTransactionStatus::Draft; }
    public function isPending(): bool         { return $this->status === TreasuryTransactionStatus::PendingApproval; }
    public function isCompleted(): bool       { return $this->status === TreasuryTransactionStatus::Completed; }
    public function isReversed(): bool        { return $this->status === TreasuryTransactionStatus::Reversed; }
    public function isReversible(): bool      { return $this->status->canBeReversed() && is_null($this->reversed_by_id); }

    // ── Computed ───────────────────────────────────────────────────────────────

    public function typeIcon(): string   { return $this->transaction_type?->icon() ?? ''; }
    public function typeLabel(): string  { return $this->transaction_type?->label() ?? ''; }

    /** Display the net movement in a human-readable direction */
    public function directionLabel(): string
    {
        $from = $this->fromAccount?->name;
        $to   = $this->toAccount?->name;

        if ($from && $to)   return "{$from} → {$to}";
        if ($from)          return "Out of {$from}";
        if ($to)            return "Into {$to}";
        return '—';
    }

    public function netAmount(): float { return (float) $this->amount - (float) $this->fee_amount; }
}