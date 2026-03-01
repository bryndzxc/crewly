<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\HireApplicantRequest;
use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;
use App\Services\ApplicantHireService;

class ApplicantHireController extends Controller
{
    public function __construct(
        private readonly ApplicantHireService $applicantHireService,
    ) {}

    public function store(HireApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
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
