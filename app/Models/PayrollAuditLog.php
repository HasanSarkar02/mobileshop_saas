<?php

namespace App\Models;

use App\Enums\PayrollAuditAction;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'shop_id', 'user_id', 'reference_type', 'reference_id',
    'action', 'old_status', 'new_status', 'amount',
    'reason', 'ip_address', 'user_agent', 'metadata',
])]
class PayrollAuditLog extends Model
{
    public const UPDATED_AT = null; // only created_at

    protected function casts(): array
    {
        return [
            'action'   => PayrollAuditAction::class,
            'amount'   => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function reference(): MorphTo { return $this->morphTo(); }

    public static function record(
        int                $shopId,
        string             $referenceType,
        int                $referenceId,
        PayrollAuditAction $action,
        ?string            $oldStatus = null,
        ?string            $newStatus = null,
        ?float             $amount    = null,
        ?string            $reason    = null,
        array              $metadata  = [],
    ): self {
        return static::create([
            'shop_id'        => $shopId,
            'user_id'        => \Illuminate\Support\Facades\Auth::id(),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'action'         => $action->value,
            'old_status'     => $oldStatus,
            'new_status'     => $newStatus,
            'amount'         => $amount,
            'reason'         => $reason,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'metadata'       => $metadata ?: null,
        ]);
    }
}