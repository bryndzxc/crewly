<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreApplicantRequest;
use App\Http\Requests\Recruitment\UpdateApplicantRequest;
use App\Models\Applicant;
use App\Services\ApplicantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantController extends Controller
{
    public function __construct(
        private readonly ApplicantService $applicantService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Recruitment/Applicants/Index', $this->applicantService->index($request));
    }

    public function create(): Response
    {
        return Inertia::render('Recruitment/Applicants/Create', $this->applicantService->createPayload());
    }

    public function store(StoreApplicantRequest $request): RedirectResponse
    {
        $returnTo = (string) $request->input('return_to', 'show');

        $applicant = $this->applicantService->create(
            $request->validated(),
            $request->user()?->id,
            $request->file('resume')
        );

        if ($returnTo === 'index') {
            return to_route('recruitment.applicants.index')
                ->with('success', 'Applicant created successfully.')
                ->setStatusCode(303);
        }

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Applicant created successfully.')
            ->setStatusCode(303);
    }

    public function show(Request $request, Applicant $applicant): Response
    {
        return Inertia::render('Recruitment/Applicants/Show', $this->applicantService->showPayload($request, $applicant));
    }

    public function edit(Applicant $applicant): Response
    {
        return Inertia::render('Recruitment/Applicants/Edit', $this->applicantService->editPayload($applicant));
    }

    public function update(UpdateApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
        $returnTo = (string) $request->input('return_to', 'show');

        $this->applicantService->update($applicant, $request->validated());

        if ($returnTo === 'index') {
            return to_route('recruitment.applicants.index')
                ->with('success', 'Applicant updated successfully.')
                ->setStatusCode(303);
        }

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Applicant updated successfully.')
            ->setStatusCode(303);
    }

    public function destroy(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->applicantService->delete($applicant);

        return to_route('recruitment.applicants.index')
            ->with('success', 'Applicant deleted successfully.')
            ->setStatusCode(303);
    }
}
