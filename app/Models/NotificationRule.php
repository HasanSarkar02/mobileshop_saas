<?php

namespace App\Models;

use App\Enums\NotificationEventType;
use App\Enums\NotificationPriority;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'event_type', 'name', 'is_active', 'conditions',
    'channel_override', 'priority_override', 'recipient_override_type',
    'recipient_override_permission', 'recipient_override_user_ids',
    'sort_order', 'created_by',
])]
class NotificationRule extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'is_active' => 'boolean',
            'conditions' => 'array',
            'channel_override' => 'array',
            'priority_override' => NotificationPriority::class,
            'recipient_override_user_ids' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}