<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('show-wish', function (User $user, Wish $wish) {
            if (!$wish->wishlist->is_private) {
                return true;
            }
            return $user->id === $wish->wishlist->user_id;
        });

        Gate::define('update-wish', function (User $user, Wish $wish) {
            return $user->id === $wish->wishlist->user_id;
        });

        Gate::define('delete-wish', function (User $user, Wish $wish) {
            return $user->id === $wish->wishlist->user_id;
        });
    }
}
