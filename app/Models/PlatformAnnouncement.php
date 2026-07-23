<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'body', 'type', 'audience', 'is_active', 'dismissible', 'starts_at', 'ends_at', 'created_by'])]
class PlatformAnnouncement extends Model
{
    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'dismissible' => 'boolean',
            'starts_at'   => 'datetime',
            'ends_at'     => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('audience', 'both')->orWhere('audience', $audience);
        });
    }
}