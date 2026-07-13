<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'notification_id', 'user_id', 'shop_id', 'delivered_at', 'read_at',
    'dismissed_at', 'archived_at', 'pinned_at', 'snoozed_until', 'action_taken_at',
])]
class NotificationRecipient extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'archived_at' => 'datetime',
            'pinned_at' => 'datetime',
            'snoozed_until' => 'datetime',
            'action_taken_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    public function isActive(): bool
    {
        return $this->dismissed_at === null
            && $this->archived_at === null
            && ! $this->isSnoozed();
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}