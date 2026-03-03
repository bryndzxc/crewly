<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\HireApplicantRequest;
use App\Models\Applicant;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use App\Services\ApplicantHireService;

class ApplicantHireController extends Controller
{
    public function __construct(
        private readonly ApplicantHireService $applicantHireService,
    ) {}

    public function store(HireApplicantRequest $request, Applicant $applicant, PlanLimitService $planLimitService): RedirectResponse
    {
        $user = $request->user();
        $companyId = (int) ($user?->company_id ?? 0);

        if ($companyId > 0 && $user && !$user->isDeveloper()) {
            $usage = $planLimitService->employeeUsage($companyId);

            if (($usage['max'] ?? 0) > 0 && (int) ($usage['used'] ?? 0) >= (int) ($usage['max'] ?? 0)) {
                $max = (int) ($usage['max'] ?? 0);
                session()->flash('error', "You've reached your plan limit of {$max} active employees. Contact support to upgrade.");
                session()->flash('upgrade_url', route('chat.support', [
                    'message' => "Hi! We reached our plan limit ({$max} active employees) while trying to hire an applicant. Please help us upgrade.",
                ]));
                session()->flash('upgrade_label', 'Contact support to upgrade');

                return back()->setStatusCode(303);
            }
        }

        $employee = $this->applicantHireService->hire($applicant, $request->validated(), $request->user()?->id);

        $existingSuccess = (string) session()->get('success', '');
        $passwordReminder = '';

        if ($existingSuccess !== '' && str_contains($existingSuccess, 'Default password:')) {
            $pos = strpos($existingSuccess, 'Portal account created.');
            $passwordReminder = $pos !== false
                ? trim(substr($existingSuccess, $pos))
                : trim($existingSuccess);
        }

        $message = 'Applicant hired and employee created successfully.';
        if ($passwordReminder !== '') {
            $message = trim($message . ' ' . $passwordReminder);
        }

        return to_route('employees.show', $employee->employee_id)
            ->with('success', $message)
            ->setStatusCode(303);
    }
}
