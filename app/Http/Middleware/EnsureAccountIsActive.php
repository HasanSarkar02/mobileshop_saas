<?php

namespace App\Http\Middleware;

use App\Enums\ShopStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return $next($request);
        }

        $blocked = ! $user->is_active;

        if (! $blocked && $user->shop_id) {
            $shop = $user->shop;
            $blocked = ! $shop || ! $shop->is_active
                || in_array($shop->status, [ShopStatus::Suspended, ShopStatus::Expired], true);
        }

        if ($blocked) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account or shop is currently inactive. Please contact support.',
            ]);
        }

        return $next($request);
    }
}