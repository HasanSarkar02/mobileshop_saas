<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function create()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $request->email)
            ->where('user_type', UserType::SuperAdmin)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->is_active) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => 'This admin account has been deactivated.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Password verified — hold pending 2FA state, do NOT log in yet.
        $request->session()->put('admin_2fa.user_id', $user->id);
        $request->session()->put('admin_2fa.remember', $request->boolean('remember'));

        return redirect()->route('admin.2fa.challenge');
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function showTwoFactorChallenge(Request $request)
    {
        $userId = $request->session()->get('admin_2fa.user_id');
        if (! $userId) {
            return redirect()->route('admin.login');
        }

        $user = User::where('user_type', UserType::SuperAdmin)->findOrFail($userId);
        $needsSetup = is_null($user->two_factor_confirmed_at);

        $setupData = null;
        if ($needsSetup) {
            if (! $user->two_factor_secret) {
                $secret = app(TwoFactorAuthService::class)->generateSecretKey();
                $user->forceFill(['two_factor_secret' => $secret])->save();
            }
            $tfa = app(TwoFactorAuthService::class);
            $setupData = [
                'secret' => $user->two_factor_secret,
                'qrUrl'  => $tfa->getQrCodeUrl(config('app.name', 'ShopERP Admin'), $user->email, $user->two_factor_secret),
            ];
        }

        return view('admin.two-factor-challenge', compact('needsSetup', 'setupData'));
    }

    public function verifyTwoFactorChallenge(Request $request, TwoFactorAuthService $tfa)
    {
        $userId = $request->session()->get('admin_2fa.user_id');
        if (! $userId) {
            return redirect()->route('admin.login');
        }

        $user = User::where('user_type', UserType::SuperAdmin)->findOrFail($userId);

        $request->validate(['code' => ['required', 'string']]);

        $throttleKey = 'admin-2fa|' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'code' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $code = trim($request->input('code'));
        $verified = false;

        if ($user->two_factor_secret && $tfa->verifyCode($user->two_factor_secret, $code)) {
            $verified = true;
        } elseif ($user->two_factor_confirmed_at && $tfa->verifyAndConsumeRecoveryCode($user, $code)) {
            $verified = true;
        }

        if (! $verified) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'code' => 'Invalid authentication code.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $recoveryCodesToShow = null;

        if (is_null($user->two_factor_confirmed_at)) {
            $plainCodes = $tfa->generateRecoveryCodes();
            $user->forceFill([
                'two_factor_confirmed_at'   => now(),
                'two_factor_recovery_codes' => $tfa->hashRecoveryCodes($plainCodes),
            ])->save();
            $recoveryCodesToShow = $plainCodes;
        }

        $remember = $request->session()->pull('admin_2fa.remember', false);
        $request->session()->forget('admin_2fa.user_id');

        Auth::guard('admin')->login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        if ($recoveryCodesToShow) {
            $request->session()->flash('new_recovery_codes', $recoveryCodesToShow);
            return redirect()->route('admin.2fa.recovery-codes.show');
        }

        return redirect()->route('admin.dashboard');
    }

    public function showRecoveryCodesOnce(Request $request)
    {
        $codes = $request->session()->get('new_recovery_codes');
        if (! $codes || ! Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.two-factor-recovery-codes', ['codes' => $codes]);
    }
}