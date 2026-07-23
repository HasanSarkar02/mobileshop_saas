<?php

namespace App\Services;

use App\Events\ImpersonationStarted;
use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImpersonationService
{
    private const MAX_IMPERSONATION_MINUTES = 30;

    public function start(Request $request, User $superAdmin, User $target, ?string $reason = null): void
    {
        if (! $superAdmin->isSuperAdmin()) {
            throw new RuntimeException('Only Super Admins can impersonate.');
        }

        if (! $target->shop_id) {
            throw new RuntimeException('Cannot impersonate an account with no shop.');
        }

        if ($target->isSuperAdmin()) {
            throw new RuntimeException('Super Admin accounts cannot be impersonated. This prevents privilege laundering through nested identity switches.');
        }

        if ($target->id === $superAdmin->id) {
            throw new RuntimeException('Cannot impersonate your own account.');
        }

        $log = ImpersonationLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'shop_id' => $target->shop_id,
            'reason' => $reason,
            'started_at' => now(),
        ]);

        $request->session()->regenerate();

        $request->session()->put('impersonation_log_id', $log->id);
        $request->session()->put('impersonator_id', $superAdmin->id);
        $request->session()->put('impersonation_started_at', now()->timestamp);

        Auth::guard('admin')->logout();
        Auth::guard('web')->login($target);
        $targetShop = \App\Models\Shop::withoutGlobalScopes()->find($target->shop_id);
        if ($targetShop) {
            DB::afterCommit(fn () => event(new ImpersonationStarted($target, $superAdmin, $targetShop)));
        }
    }

    public function stop(Request $request): void
    {
        $logId = $request->session()->pull('impersonation_log_id');

        if ($logId) {
            ImpersonationLog::where('id', $logId)->update(['ended_at' => now()]);
        }

        $request->session()->forget(['impersonation_log_id', 'impersonator_id', 'impersonation_started_at']);

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();

        $request->session()->regenerate();

    }

    public function hasExpired(Request $request): bool
    {
        $startedAt = $request->session()->get('impersonation_started_at');

        if (! $startedAt) {
            return false;
        }

        return now()->timestamp - $startedAt > (self::MAX_IMPERSONATION_MINUTES * 60);
    }
}