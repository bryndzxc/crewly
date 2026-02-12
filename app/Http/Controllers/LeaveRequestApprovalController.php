<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveLeaveRequestRequest;
use App\Http\Requests\DenyLeaveRequestRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\LeaveRequestService;

class LeaveRequestApprovalController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    public function approve(ApproveLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be approved.')->setStatusCode(303);
        }

        $leaveRequest->load(['leaveType']);

        if ($leaveRequest->leaveType && !$leaveRequest->leaveType->requires_approval) {
            return back()->with('error', 'This leave type does not require approval.')->setStatusCode(303);
        }

        $this->leaveRequestService->approve($leaveRequest, $request->validated(), $request->user()?->id);

        return to_route('leave.requests.show', $leaveRequest)->with('success', 'Leave request approved.')->setStatusCode(303);
    }

    public function deny(DenyLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be denied.')->setStatusCode(303);
        }

        $this->leaveRequestService->deny($leaveRequest, $request->validated(), $request->user()?->id);

        return to_route('leave.requests.show', $leaveRequest)->with('success', 'Leave request denied.')->setStatusCode(303);
    }
}
