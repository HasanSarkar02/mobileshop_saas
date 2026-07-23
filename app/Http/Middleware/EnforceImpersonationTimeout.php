<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;

class EnforceImpersonationTimeout
{
    public function __construct(private readonly ImpersonationService $impersonation) {}

    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('impersonation_log_id') && $this->impersonation->hasExpired($request)) {
            $this->impersonation->stop($request);

            return redirect()->route('admin.login')
                ->withErrors(['session' => 'Your impersonation session expired for security reasons. Please log in again.']);
        }

        return $next($request);
    }
}