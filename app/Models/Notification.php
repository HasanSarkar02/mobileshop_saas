<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use App\Enums\NotificationEventType;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'shop_id', 'branch_id', 'event_type', 'category', 'priority', 'status',
    'title', 'body', 'icon', 'reference_type', 'reference_id',
    'action_required', 'action_label', 'group_key', 'occurrence_count',
    'last_occurred_at', 'escalation_level', 'escalated_at', 'payload', 'created_by',
])]
class Notification extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'category' => NotificationCategory::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
            'action_required' => 'boolean',
            'occurrence_count' => 'integer',
            'last_occurred_at' => 'datetime',
            'escalation_level' => 'integer',
            'escalated_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    /**
     * A notification's content is an audit record of "this happened" — never
     * let title/body/reference/category be edited after the fact. Only the
     * header's own lifecycle fields may change post-creation: status (as
     * deliveries complete), the occurrence/grouping counters (recurring same
     * problem), and escalation fields + priority (EscalatePendingNotifications
     * deliberately bumps priority as part of escalating — that is a lifecycle
     * transition, not a content edit).
     *
     * Mirrors JournalEntry::save()'s immutability guard.
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            $dirtyKeys = array_keys($this->getDirty());
            $allowedKeys = [
                'status', 'occurrence_count', 'last_occurred_at',
                'escalation_level', 'escalated_at', 'priority',
            ];

            if (! empty($dirtyKeys) && ! empty(array_diff($dirtyKeys, $allowedKeys))) {
                throw new \RuntimeException(
                    'Notifications are immutable once created — only lifecycle fields (status, ' .
                    'occurrence/grouping counters, escalation, priority) may be updated.'
                );
            }
        }

        return parent::save($options);
    }
}