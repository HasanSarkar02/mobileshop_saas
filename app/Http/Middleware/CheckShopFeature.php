<?php

namespace App\Http\Middleware;

use App\Services\ShopFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckShopFeature
{
    public function __construct(
        private readonly ShopFeatureService $features
    ) {}

    /**
     * Usage:
     * ->middleware('feature:inventory')
     * ->middleware('feature:service')
     * ->middleware('feature:payroll')
     */
    public function handle(
        Request $request,
        Closure $next,
        string $feature
    ): Response
    {
        if (! $this->features->enabled($feature)) {

            $isLivewire = $request->hasHeader('X-Livewire')
                || $request->is('livewire/*');

            if ($request->expectsJson() || $isLivewire) {
                abort(Response::HTTP_FORBIDDEN, 'Feature not enabled.');
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'This feature is not available on your current plan.');
        }

        return $next($request);
    }
}