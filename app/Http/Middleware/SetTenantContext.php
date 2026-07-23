<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clear();

        $isImpersonating = $request->session()->has('impersonation_log_id');

        // === SUPER ADMIN / ADMIN GUARD === 
        if (Auth::guard('admin')->check() && ! $isImpersonating) {
         app(PermissionRegistrar::class)->setPermissionsTeamId(null);
         return $next($request);
     }

        // === NORMAL TENANT USER ===
        if (auth()->check() && auth()->user()?->shop_id) {
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