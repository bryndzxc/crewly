<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMyLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Repositories\LeaveRequestRepository;
use App\Services\ActivityLogService;
use App\Services\AuditLogger;
use App\Services\EmployeeResolver;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MyLeaveController extends Controller
{
    public function __construct(
        private readonly EmployeeResolver $employeeResolver,
        private readonly LeaveRequestRepository $leaveRequestRepository,
        private readonly ActivityLogService $activityLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('access-my-portal');

        $employee = $this->employeeResolver->requireCurrent($request->user());

        $requests = LeaveRequest::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->with(['leaveType:id,name,code'])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(function (LeaveRequest $lr) {
                return [
                    'id' => (int) $lr->id,
                    'start_date' => $lr->start_date?->format('Y-m-d'),
                    'end_date' => $lr->end_date?->format('Y-m-d'),
                    'status' => (string) $lr->status,
                    'created_at' => $lr->created_at?->format('Y-m-d H:i:s'),
                    'leave_type' => $lr->leaveType ? $lr->leaveType->only(['id', 'name', 'code']) : null,
                ];
            });

        return Inertia::render('My/Leave/Index', [
            'requests' => $requests,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('access-my-portal');

        // Ensure linked employee exists.
        $this->employeeResolver->requireCurrent($request->user());

        return Inertia::render('My/Leave/Create', [
            'leaveTypes' => $this->leaveRequestRepository->getActiveLeaveTypes(),
        ]);
    }

    public function store(StoreMyLeaveRequestRequest $request): RedirectResponse
    {
        $this->authorize('access-my-portal');

        $employee = $this->employeeResolver->requireCurrent($request->user());
        $validated = $request->validated();

        /** @var LeaveType $type */
        $type = LeaveType::query()->findOrFail((int) $validated['leave_type_id']);

        $leaveRequest = DB::transaction(function () use ($employee, $validated, $type, $request) {
            $lr = new LeaveRequest();
            $lr->employee_id = (int) $employee->employee_id;
            $lr->leave_type_id = (int) $validated['leave_type_id'];
            $lr->start_date = Carbon::parse((string) $validated['start_date'])->toDateString();
            $lr->end_date = Carbon::parse((string) $validated['end_date'])->toDateString();
            $lr->is_half_day = (bool) ($validated['is_half_day'] ?? false);
            $lr->half_day_part = $validated['half_day_part'] ?? null;
            $lr->reason = $validated['reason'] ?? null;

            // Employee self-service submissions are always queued for review.
            $lr->status = LeaveRequest::STATUS_PENDING;
            $lr->requested_by = $request->user()?->id;
            $lr->approved_by = null;
            $lr->approved_at = null;
            $lr->denied_by = null;
            $lr->denied_at = null;
            $lr->decision_notes = null;
            $lr->save();

            $this->activityLogService->log('requested', $lr, [
                'employee_id' => (int) $lr->employee_id,
                'leave_type_id' => (int) $lr->leave_type_id,
                'start_date' => Carbon::parse((string) $lr->getRawOriginal('start_date'))->toDateString(),
                'end_date' => Carbon::parse((string) $lr->getRawOriginal('end_date'))->toDateString(),
                'is_half_day' => (bool) $lr->is_half_day,
                'half_day_part' => $lr->half_day_part,
                'status' => (string) $lr->status,
                'total_days' => $lr->total_days,
                'type_requires_approval' => (bool) ($type->requires_approval ?? true),
            ], 'Leave request submitted.');

            app(AuditLogger::class)->log(
                'leave.requested',
                $lr,
                [],
                [
                    'employee_id' => (int) $lr->employee_id,
                    'leave_type_id' => (int) $lr->leave_type_id,
                    'start_date' => Carbon::parse((string) $lr->getRawOriginal('start_date'))->toDateString(),
                    'end_date' => Carbon::parse((string) $lr->getRawOriginal('end_date'))->toDateString(),
                    'is_half_day' => (bool) $lr->is_half_day,
                    'half_day_part' => $lr->half_day_part,
                    'status' => (string) $lr->status,
                    'total_days' => $lr->total_days,
                ],
                [],
                'Leave request submitted.'
            );

            return $lr;
        });

        $this->notificationService->notifyLeaveSubmitted($leaveRequest);

        return to_route('my.leave.index')
            ->with('success', 'Leave request submitted.')
            ->setStatusCode(303);
    }
}
