<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability, array $arguments = []) {
            return $user->isDeveloper() ? true : null;
        });

        Gate::define('manage-users', fn (User $user) => $user->hasRole(User::ROLE_ADMIN));
        Gate::define('manage-roles', fn (User $user) => $user->hasRole(User::ROLE_ADMIN));

        Gate::define('access-employees', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('access-recruitment', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('employees-documents-download', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('employees-documents-upload', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('employees-documents-delete', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));
    }
}
