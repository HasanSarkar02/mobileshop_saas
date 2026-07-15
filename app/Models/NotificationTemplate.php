<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\Concerns\BelongsToShopOrGlobal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['shop_id', 'event_type', 'channel', 'subject', 'body', 'is_active'])]
class NotificationTemplate extends Model
{
    use BelongsToShopOrGlobal;

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'channel' => NotificationChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function isSystemDefault(): bool
    {
        return $this->shop_id === null;
    }
}