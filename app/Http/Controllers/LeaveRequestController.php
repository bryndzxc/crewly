<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return Inertia::render('Leave/Requests/Index', $this->leaveRequestService->index($request));
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', LeaveRequest::class);

        return Inertia::render('Leave/Requests/Create', $this->leaveRequestService->createForm());
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $leaveRequest = $this->leaveRequestService->create($request->validated(), $request->user()?->id);

        return to_route('leave.requests.show', $leaveRequest)->with('success', 'Leave request created successfully.')->setStatusCode(303);
    }

    public function show(Request $request, LeaveRequest $leaveRequest): Response
    {
        $this->authorize('view', $leaveRequest);

        return Inertia::render('Leave/Requests/Show', $this->leaveRequestService->show($request, $leaveRequest));
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('cancel', $leaveRequest);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be cancelled.')->setStatusCode(303);
        }

        $this->leaveRequestService->cancel($leaveRequest);

        return to_route('leave.requests.show', $leaveRequest)->with('success', 'Leave request cancelled.')->setStatusCode(303);
    }
}
