<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreRecruitmentPositionRequest;
use App\Http\Requests\Recruitment\UpdateRecruitmentPositionRequest;
use App\Models\RecruitmentPosition;
use App\Services\RecruitmentPositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecruitmentPositionController extends Controller
{
    public function __construct(
        private readonly RecruitmentPositionService $recruitmentPositionService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Recruitment/Positions/Index', $this->recruitmentPositionService->index($request));
    }

    public function create(): Response
    {
        return Inertia::render('Recruitment/Positions/Create');
    }

    public function store(StoreRecruitmentPositionRequest $request): RedirectResponse
    {
        $this->recruitmentPositionService->create($request->validated(), $request->user()?->id);

        return to_route('recruitment.positions.index')
            ->with('success', 'Position created successfully.')
            ->setStatusCode(303);
    }

    public function edit(RecruitmentPosition $position): Response
    {
        return Inertia::render('Recruitment/Positions/Edit', $this->recruitmentPositionService->editPayload($position));
    }

    public function update(UpdateRecruitmentPositionRequest $request, RecruitmentPosition $position): RedirectResponse
    {
        $this->recruitmentPositionService->update($position, $request->validated());

        return to_route('recruitment.positions.index')
            ->with('success', 'Position updated successfully.')
            ->setStatusCode(303);
    }

    public function destroy(Request $request, RecruitmentPosition $position): RedirectResponse
    {
        $this->recruitmentPositionService->delete($position);

        return to_route('recruitment.positions.index')
            ->with('success', 'Position deleted successfully.')
            ->setStatusCode(303);
    }
}
