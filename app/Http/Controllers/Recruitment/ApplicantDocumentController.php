<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreApplicantDocumentRequest;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Services\ApplicantDocumentService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantDocumentController extends Controller
{
    public function __construct(
        private readonly ApplicantDocumentService $applicantDocumentService,
    )
    {
    }

    public function store(StoreApplicantDocumentRequest $request, Applicant $applicant): RedirectResponse
    {
        $validated = $request->validated();
        $files = $request->file('files', []);

        $this->applicantDocumentService->uploadMany($applicant, $validated, $files, $request->user()?->id);

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Document(s) uploaded successfully.')
            ->setStatusCode(303);
    }

    public function download(Applicant $applicant, ApplicantDocument $document): StreamedResponse
    {
        return $this->applicantDocumentService->download($applicant, $document);
    }

    public function destroy(Applicant $applicant, ApplicantDocument $document): RedirectResponse
    {
        $this->applicantDocumentService->delete($applicant, $document);

        return to_route('recruitment.applicants.show', $applicant->id)
            ->with('success', 'Document deleted successfully.')
            ->setStatusCode(303);
    }
}
