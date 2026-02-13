<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreApplicantInterviewRequest;
use App\Http\Requests\Recruitment\UpdateApplicantInterviewRequest;
use App\Models\Applicant;
use App\Models\ApplicantInterview;
use App\Services\ApplicantInterviewService;
use Illuminate\Http\RedirectResponse;

class ApplicantInterviewController extends Controller
{
    public function __construct(
        private readonly ApplicantInterviewService $applicantInterviewService,
    ) {}

    public function store(StoreApplicantInterviewRequest $request, Applicant $applicant): RedirectResponse
    {
        $this->applicantInterviewService->create($applicant, $request->validated(), $request->user()?->id);

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Interview note added.')
            ->setStatusCode(303);
    }

    public function update(UpdateApplicantInterviewRequest $request, Applicant $applicant, ApplicantInterview $interview): RedirectResponse
    {
        $this->applicantInterviewService->update($applicant, $interview, $request->validated(), $request->user()?->id);

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Interview updated.')
            ->setStatusCode(303);
    }

    public function destroy(Applicant $applicant, ApplicantInterview $interview): RedirectResponse
    {
        $this->applicantInterviewService->delete($applicant, $interview);

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Interview deleted.')
            ->setStatusCode(303);
    }
}
