<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\DTO\LeadCreateData;
use App\Http\Requests\StoreAccessRequestRequest;
use App\Models\Company;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly LeadService $leadService)
    {
    }

    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        $plan = strtolower(trim((string) $request->query('plan', '')));
        $allowed = [Company::PLAN_STARTER, Company::PLAN_GROWTH, Company::PLAN_PRO];

        return Inertia::render('Auth/Register', [
            'requested_plan_default' => in_array($plan, $allowed, true) ? $plan : '',
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreAccessRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['source_page'] = $validated['source_page'] ?? 'register';

        $dto = LeadCreateData::fromArray($validated);
        $this->leadService->submitAccessRequest($dto, $request->user()?->email);

        return redirect()
            ->route('register')
            ->with('success', 'Thanks — we received your request. We’ll email you once your access is approved.')
            ->setStatusCode(303);
    }
}
