<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Authorization gates
        Gate::define('view_personal_transactions', function (User $user) {
            return $user->isAdmin() && (bool) ($user->can_view_personal_transactions ?? false);
        });
    }
}
