<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SuperAdmin and Owner bypass ALL permission checks.
        // Employees go through Spatie permission checks.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) return true;
            if ($user->isOwner()) return true;
            return null; // Employee → checked by Spatie
        });
    }
}