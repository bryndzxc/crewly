<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\LeaveTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function __construct(
        private readonly LeaveTypeService $leaveTypeService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LeaveType::class);

        return Inertia::render('Leave/Types/Index', $this->leaveTypeService->index($request));
    }

    public function create(): Response
    {
        $this->authorize('create', LeaveType::class);

        return Inertia::render('Leave/Types/Create');
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $this->leaveTypeService->create($request->validated(), $request->user()?->id);

        return to_route('leave.types.index')->with('success', 'Leave type created successfully.')->setStatusCode(303);
    }

    public function edit(LeaveType $type): Response
    {
        $this->authorize('update', $type);

        return Inertia::render('Leave/Types/Edit', $this->leaveTypeService->editPayload($type));
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $type): RedirectResponse
    {
        $this->leaveTypeService->update($type, $request->validated());

        return to_route('leave.types.index')->with('success', 'Leave type updated successfully.')->setStatusCode(303);
    }
}
