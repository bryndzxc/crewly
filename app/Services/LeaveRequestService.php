<?php

namespace App\Services;

use App\DTO\LeaveDecisionData;
use App\DTO\LeaveRequestData;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Repositories\LeaveRequestRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService extends Service
{
    public function __construct(
        private readonly LeaveRequestRepository $leaveRequestRepository,
    ) {}

    public function index(Request $request): array
    {
        $status = trim((string) $request->query('status', ''));
        $leaveTypeId = (int) $request->query('leave_type_id', 0);
        $q = trim((string) $request->query('q', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $canCreate = $request->user()?->can('create', LeaveRequest::class) ?? false;

        $requests = $this->leaveRequestRepository->paginateIndex(
            status: $status,
            leaveTypeId: $leaveTypeId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            perPage: $perPage,
        );

        $leaveTypes = $this->leaveRequestRepository->getActiveLeaveTypes();

        $employees = [];
        if ($canCreate) {
            $employees = $this->leaveRequestRepository->getEmployeesForCreate();
        }

        return [
            'requests' => $requests,
            'leaveTypes' => $leaveTypes,
            'employees' => $employees,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'leave_type_id' => $leaveTypeId ?: null,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
            'actions' => [
                'create' => $canCreate,
            ],
        ];
    }

    public function createForm(): array
    {
        return [
            'employees' => $this->leaveRequestRepository->getEmployeesForCreate(),
            'leaveTypes' => $this->leaveRequestRepository->getActiveLeaveTypes(),
        ];
    }

    public function create(array $validated, ?int $userId): LeaveRequest
    {
        $dto = LeaveRequestData::fromArray($validated);

        /** @var LeaveType $type */
        $type = LeaveType::query()->findOrFail($dto->leave_type_id);

        return $this->leaveRequestRepository->create($dto, $type, $userId, Carbon::now());
    }

    public function show(Request $request, LeaveRequest $requestModel): array
    {
        $requestModel->load([
            'employee:employee_id,employee_code,first_name,middle_name,last_name,suffix',
            'leaveType:id,name,code,paid,allow_half_day,requires_approval',
            'requestedBy:id,name,role',
            'approvedBy:id,name,role',
            'deniedBy:id,name,role',
        ]);

        return [
            'request' => [
                'id' => $requestModel->id,
                'employee_id' => $requestModel->employee_id,
                'leave_type_id' => $requestModel->leave_type_id,
                'start_date' => $requestModel->start_date?->format('Y-m-d'),
                'end_date' => $requestModel->end_date?->format('Y-m-d'),
                'is_half_day' => (bool) $requestModel->is_half_day,
                'half_day_part' => $requestModel->half_day_part,
                'reason' => $requestModel->reason,
                'status' => $requestModel->status,
                'total_days' => $requestModel->total_days,
                'decision_notes' => $requestModel->decision_notes,
                'requested_by' => $requestModel->requested_by,
                'approved_by' => $requestModel->approved_by,
                'approved_at' => $requestModel->approved_at?->format('Y-m-d H:i:s'),
                'denied_by' => $requestModel->denied_by,
                'denied_at' => $requestModel->denied_at?->format('Y-m-d H:i:s'),
                'created_at' => $requestModel->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $requestModel->updated_at?->format('Y-m-d H:i:s'),
            ],
            'employee' => $requestModel->employee ? $requestModel->employee->only([
                'employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix',
            ]) : null,
            'leaveType' => $requestModel->leaveType ? $requestModel->leaveType->only([
                'id', 'name', 'code', 'paid', 'allow_half_day', 'requires_approval',
            ]) : null,
            'actors' => [
                'requested_by' => $requestModel->requestedBy ? $requestModel->requestedBy->only(['id', 'name', 'role']) : null,
                'approved_by' => $requestModel->approvedBy ? $requestModel->approvedBy->only(['id', 'name', 'role']) : null,
                'denied_by' => $requestModel->deniedBy ? $requestModel->deniedBy->only(['id', 'name', 'role']) : null,
            ],
            'actions' => [
                'approve' => $request->user()?->can('approve', $requestModel) ?? false,
                'deny' => $request->user()?->can('deny', $requestModel) ?? false,
                'cancel' => $request->user()?->can('cancel', $requestModel) ?? false,
            ],
        ];
    }

    public function cancel(LeaveRequest $requestModel): LeaveRequest
    {
        return $this->leaveRequestRepository->cancel($requestModel);
    }

    public function approve(LeaveRequest $requestModel, array $validated, ?int $userId): LeaveRequest
    {
        $decision = LeaveDecisionData::fromArray($validated);

        return DB::transaction(function () use ($requestModel, $decision, $userId) {
            return $this->leaveRequestRepository->approve($requestModel, $decision, $userId, Carbon::now());
        });
    }

    public function deny(LeaveRequest $requestModel, array $validated, ?int $userId): LeaveRequest
    {
        $decision = LeaveDecisionData::fromArray($validated);

        return DB::transaction(function () use ($requestModel, $decision, $userId) {
            return $this->leaveRequestRepository->deny($requestModel, $decision, $userId, Carbon::now());
        });
    }
}
