<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\DeveloperLeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DemoRequestController extends Controller
{
    public function __construct(private readonly DeveloperLeadService $developerLeadService)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Developer/DemoRequests/Index', $this->developerLeadService->index($request));
    }

    public function approve(Lead $lead): RedirectResponse
    {
        try {
            $company = $this->developerLeadService->approve($lead);

            return redirect()
                ->back()
                ->with('success', "Approved. Created demo company '{$company->name}', seeded sample data, and emailed login details.")
                ->setStatusCode(303);
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->setStatusCode(303);
        }
    }

    public function decline(Lead $lead): RedirectResponse
    {
        try {
            $this->developerLeadService->decline($lead);

            return redirect()
                ->back()
                ->with('success', 'Declined.')
                ->setStatusCode(303);
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->setStatusCode(303);
        }
    }
}
