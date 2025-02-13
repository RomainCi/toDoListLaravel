<?php

namespace App\Providers;

use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('update-project', [ProjectPolicy::class, 'update']);
        Gate::define('update-role', [ProjectPolicy::class, 'updateRole']);
    }
}
