<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLogger
{
    public function log(?User $admin, string $action, ?Model $subject = null, ?string $reason = null, array $meta = []): AdminActionLog
    {
        return AdminActionLog::create([
            'admin_id'     => $admin?->id,
            'action'       => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'shop_id'      => $subject instanceof Shop ? $subject->id : ($subject->shop_id ?? null),
            'reason'       => $reason,
            'meta'         => $meta ?: null,
        ]);
    }
}