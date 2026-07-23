<?php

use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withEvents(discover: false)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureAccountIsActive::class,
            
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetTenantContext::class,
        ]);

        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureIsSuperAdmin::class,
            'feature' => \App\Http\Middleware\CheckShopFeature::class,
            'impersonation.timeout' => \App\Http\Middleware\EnforceImpersonationTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();