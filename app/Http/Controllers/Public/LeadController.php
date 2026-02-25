<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\DTO\LeadCreateData;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $dto = LeadCreateData::fromArray($validated);
        $this->leadService->submit($dto, $request->user()?->email);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back(303)->with('success', 'Thanks — we received your demo request and will reach out shortly.');
    }
}
