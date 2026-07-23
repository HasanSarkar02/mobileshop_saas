<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasAuthorization
{
    /**
     * Abort with 403 if the user does not have the given permission.
     * Owners and SuperAdmins always bypass permission checks.
     */
    protected function requirePermission(string $permission): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }
        
        if ($user->isOwner() || $user->isSuperAdmin()) {
            return;
        }

        if (! $user->can($permission)) {
            abort(403, "You do not have permission to perform this action. Required: {$permission}");
        }
    }

    /**
     * Check without aborting. Useful for conditional UI.
     */
    protected function hasPermission(string $permission): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if ($user->isOwner() || $user->isSuperAdmin()) return true;
        return $user->can($permission);
    }

    /**
     * Abort if feature is not enabled for this shop.
     */
    protected function requireFeature(string $feature): void
    {
        app(\App\Services\ShopFeatureService::class)->abort($feature);
    }
}