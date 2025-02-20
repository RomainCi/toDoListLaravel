<?php

namespace App\Providers;

use App\Policies\ProjectPolicy;
use App\Policies\ProjectUserPolicy;
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
        Gate::define('delete-project', [ProjectPolicy::class, 'delete']);
        Gate::define('update-role', [ProjectUserPolicy::class, 'update']);
        Gate::define('view-user', [ProjectUserPolicy::class, 'view']);
    }
}
