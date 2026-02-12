<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'can' => [
                'manageUsers' => $user ? Gate::forUser($user)->check('manage-users') : false,
                'manageRoles' => $user ? Gate::forUser($user)->check('manage-roles') : false,
                'accessEmployees' => $user ? Gate::forUser($user)->check('access-employees') : false,
                'accessRecruitment' => $user ? Gate::forUser($user)->check('access-recruitment') : false,
                'employeeDocumentsDownload' => $user ? Gate::forUser($user)->check('employees-documents-download') : false,
                'employeeDocumentsUpload' => $user ? Gate::forUser($user)->check('employees-documents-upload') : false,
                'employeeDocumentsDelete' => $user ? Gate::forUser($user)->check('employees-documents-delete') : false,
            ],
        ];
    }
}
