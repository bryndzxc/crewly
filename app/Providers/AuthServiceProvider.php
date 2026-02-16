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
        \App\Models\LeaveType::class => \App\Policies\LeaveTypePolicy::class,
        \App\Models\LeaveRequest::class => \App\Policies\LeaveRequestPolicy::class,
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

        // Employee self-service portal
        Gate::define('access-my-portal', fn (User $user) => $user->hasRole(User::ROLE_EMPLOYEE));

        Gate::define('access-employees', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('access-recruitment', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        // Recruitment (ATS-lite)
        Gate::define('recruitment-manage', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('recruitment-stage-update', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('recruitment-hire', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('recruitment-documents-download', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('recruitment-documents-upload', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('recruitment-documents-delete', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('recruitment-interviews-create', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('recruitment-interviews-manage', fn (User $user) => in_array($user->role(), [
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

        // Employee Relations (Notes & Incidents)
        Gate::define('employees-relations-view', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('employees-relations-manage', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('access-leaves', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('manage-leave-types', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('create-leave-requests', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        Gate::define('approve-leave-requests', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        // Attendance
        Gate::define('access-attendance', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('manage-attendance', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        // Payroll Summary
        Gate::define('access-payroll-summary', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
            User::ROLE_MANAGER,
        ], true));

        Gate::define('export-payroll-summary', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));

        // Audit Logs
        Gate::define('view-audit-logs', fn (User $user) => in_array($user->role(), [
            User::ROLE_ADMIN,
            User::ROLE_HR,
        ], true));
    }
}
