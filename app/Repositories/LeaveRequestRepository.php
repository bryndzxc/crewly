<?php

namespace App\Repositories;

use App\DTO\LeaveDecisionData;
use App\DTO\LeaveRequestData;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class LeaveRequestRepository extends BaseRepository
{
    public function paginateIndex(
        string $status,
        int $leaveTypeId,
        string $q,
        string $dateFrom,
        string $dateTo,
        int $perPage
    ): LengthAwarePaginator {
        $query = LeaveRequest::query()
            ->with([
                'employee:employee_id,employee_code,first_name,middle_name,last_name,suffix',
                'leaveType:id,name,code',
            ]);

        if ($status !== '' && in_array($status, [
            LeaveRequest::STATUS_PENDING,
            LeaveRequest::STATUS_APPROVED,
            LeaveRequest::STATUS_DENIED,
            LeaveRequest::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($leaveTypeId > 0) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        if ($dateFrom !== '') {
            $query->whereDate('start_date', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('end_date', '<=', $dateTo);
        }

        if ($q !== '') {
            $query->whereHas('employee', function ($empQ) use ($q) {
                $empQ->searchable($q);
            });
        }

        return $query
            ->orderByDesc('id')
            ->pagination($perPage);
    }

    /** @return \Illuminate\Support\Collection<int, LeaveType> */
    public function getActiveLeaveTypes()
    {
        return LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'allow_half_day', 'requires_approval']);
    }

    /** @return \Illuminate\Support\Collection<int, Employee> */
    public function getEmployeesForCreate(int $limit = 250)
    {
        return Employee::query()
            ->orderBy('employee_code')
            ->limit($limit)
            ->get(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix', 'status']);
    }

    public function create(LeaveRequestData $data, LeaveType $type, ?int $userId, ?Carbon $now = null): LeaveRequest
    {
        $now = $now ?? Carbon::now();

        $leaveRequest = new LeaveRequest();
        $leaveRequest->employee_id = $data->employee_id;
        $leaveRequest->leave_type_id = $data->leave_type_id;
        $leaveRequest->start_date = $data->start_date;
        $leaveRequest->end_date = $data->end_date;
        $leaveRequest->is_half_day = $data->is_half_day;
        $leaveRequest->half_day_part = $data->half_day_part;
        $leaveRequest->reason = $data->reason;
        $leaveRequest->requested_by = $userId;

        if (!$type->requires_approval) {
            $leaveRequest->status = LeaveRequest::STATUS_APPROVED;
            $leaveRequest->approved_by = $userId;
            $leaveRequest->approved_at = $now;
        } else {
            $leaveRequest->status = LeaveRequest::STATUS_PENDING;
        }

        $leaveRequest->save();

        return $leaveRequest;
    }

    public function cancel(LeaveRequest $requestModel): LeaveRequest
    {
        $requestModel->status = LeaveRequest::STATUS_CANCELLED;
        $requestModel->save();

        return $requestModel;
    }

    public function approve(LeaveRequest $requestModel, LeaveDecisionData $decision, ?int $userId, ?Carbon $now = null): LeaveRequest
    {
        $now = $now ?? Carbon::now();

        $requestModel->load(['leaveType']);

        $requestModel->status = LeaveRequest::STATUS_APPROVED;
        $requestModel->approved_by = $userId;
        $requestModel->approved_at = $now;
        $requestModel->denied_by = null;
        $requestModel->denied_at = null;
        $requestModel->decision_notes = $decision->decision_notes;
        $requestModel->save();

        $type = $requestModel->leaveType;
        if ($type && $type->default_annual_credits !== null) {
            $year = (int) ($requestModel->start_date?->format('Y') ?? $now->format('Y'));

            $balance = LeaveBalance::query()->firstOrNew([
                'employee_id' => (int) $requestModel->employee_id,
                'leave_type_id' => (int) $requestModel->leave_type_id,
                'year' => $year,
            ]);

            if (!$balance->exists) {
                $balance->credits = (float) $type->default_annual_credits;
                $balance->used = 0;
            }

            $balance->used = (float) $balance->used + (float) $requestModel->total_days;
            $balance->save();
        }

        return $requestModel;
    }

    public function deny(LeaveRequest $requestModel, LeaveDecisionData $decision, ?int $userId, ?Carbon $now = null): LeaveRequest
    {
        $now = $now ?? Carbon::now();

        $requestModel->status = LeaveRequest::STATUS_DENIED;
        $requestModel->denied_by = $userId;
        $requestModel->denied_at = $now;
        $requestModel->approved_by = null;
        $requestModel->approved_at = null;
        $requestModel->decision_notes = $decision->decision_notes;
        $requestModel->save();

        return $requestModel;
    }
}
