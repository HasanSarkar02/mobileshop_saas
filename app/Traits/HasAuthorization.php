<?php
namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasAuthorization
{
    /**
     * Abort with 403 if user doesn't have the given permission.
     * Owner always passes (Gate::before bypass handles this).
     */
    protected function requirePermission(string $permission): void
    {
        if (! Auth::user()->can($permission)) {
            abort(403, "You don't have permission: {$permission}");
        }
    }

    protected function requireAnyPermission(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            if (Auth::user()->can($permission)) return;
        }
        abort(403, 'Insufficient permissions.');
    }
}