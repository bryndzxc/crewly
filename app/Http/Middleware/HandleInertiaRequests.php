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
                'recruitmentManage' => $user ? Gate::forUser($user)->check('recruitment-manage') : false,
                'recruitmentStageUpdate' => $user ? Gate::forUser($user)->check('recruitment-stage-update') : false,
                'recruitmentHire' => $user ? Gate::forUser($user)->check('recruitment-hire') : false,
                'recruitmentDocumentsDownload' => $user ? Gate::forUser($user)->check('recruitment-documents-download') : false,
                'recruitmentDocumentsUpload' => $user ? Gate::forUser($user)->check('recruitment-documents-upload') : false,
                'recruitmentDocumentsDelete' => $user ? Gate::forUser($user)->check('recruitment-documents-delete') : false,
                'recruitmentInterviewsCreate' => $user ? Gate::forUser($user)->check('recruitment-interviews-create') : false,
                'recruitmentInterviewsManage' => $user ? Gate::forUser($user)->check('recruitment-interviews-manage') : false,
                'employeeDocumentsDownload' => $user ? Gate::forUser($user)->check('employees-documents-download') : false,
                'employeeDocumentsUpload' => $user ? Gate::forUser($user)->check('employees-documents-upload') : false,
                'employeeDocumentsDelete' => $user ? Gate::forUser($user)->check('employees-documents-delete') : false,
                'employeeRelationsView' => $user ? Gate::forUser($user)->check('employees-relations-view') : false,
                'employeeRelationsManage' => $user ? Gate::forUser($user)->check('employees-relations-manage') : false,
                'accessLeaves' => $user ? Gate::forUser($user)->check('access-leaves') : false,
                'manageLeaveTypes' => $user ? Gate::forUser($user)->check('manage-leave-types') : false,
                'createLeaveRequests' => $user ? Gate::forUser($user)->check('create-leave-requests') : false,
                'approveLeaveRequests' => $user ? Gate::forUser($user)->check('approve-leave-requests') : false,
            ],
        ];
    }
}
