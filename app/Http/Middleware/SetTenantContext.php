<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Always reset first — important if the app ever runs under Laravel
        // Octane, where static state can otherwise leak between requests.
        TenantContext::clear();

        if (auth()->check() && auth()->user()->shop_id) {
            $user = auth()->user();

            TenantContext::setShop($user->shop_id);
            TenantContext::setBranch($user->branch_id);

            app(PermissionRegistrar::class)->setPermissionsTeamId($user->shop_id);
        } else {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        return $next($request);
    }
}