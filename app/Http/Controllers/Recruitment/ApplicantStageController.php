<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\UpdateApplicantStageRequest;
use App\Models\Applicant;
use App\Services\ApplicantStageService;
use Illuminate\Http\RedirectResponse;

class ApplicantStageController extends Controller
{
    public function __construct(
        private readonly ApplicantStageService $applicantStageService,
    ) {}

    public function update(UpdateApplicantStageRequest $request, Applicant $applicant): RedirectResponse
    {
        $this->applicantStageService->updateStage($applicant, (string) $request->validated('stage'));

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Stage updated successfully.')
            ->setStatusCode(303);
    }
}
