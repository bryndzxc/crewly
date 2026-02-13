<?php

namespace App\Repositories;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeIncident;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;

class DashboardRepository extends BaseRepository
{
    public function employeesCount(): int
    {
        return Employee::query()->count();
    }

    public function expiringDocumentsCount(Carbon $today, Carbon $windowEnd): int
    {
        return EmployeeDocument::query()
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today->toDateString(), $windowEnd->toDateString()])
            ->count();
    }

    public function expiredDocumentsCount(Carbon $today): int
    {
        return EmployeeDocument::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today->toDateString())
            ->count();
    }

    public function expiringSoonDocumentsPayload(Carbon $today, Carbon $windowEnd, int $limit = 5): array
    {
        $docs = EmployeeDocument::query()
            ->select(['id', 'employee_id', 'type', 'expiry_date'])
            ->with([
                'employee' => function ($query) {
                    $query->select([
                        'employee_id',
                        'employee_code',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'suffix',
                    ]);
                },
            ])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today->toDateString(), $windowEnd->toDateString()])
            ->orderBy('expiry_date', 'asc')
            ->limit($limit)
            ->get();

        return $docs->map(function (EmployeeDocument $doc) {
            $doc->append(['expiry_status', 'days_to_expiry']);

            return [
                'id' => $doc->id,
                'type' => $doc->type,
                'expiry_date' => $doc->expiry_date?->toDateString(),
                'expiry_status' => $doc->expiry_status,
                'days_to_expiry' => $doc->days_to_expiry,
                'employee' => $doc->employee ? [
                    'employee_id' => $doc->employee->employee_id,
                    'employee_code' => $doc->employee->employee_code,
                    'first_name' => $doc->employee->first_name,
                    'middle_name' => $doc->employee->middle_name,
                    'last_name' => $doc->employee->last_name,
                    'suffix' => $doc->employee->suffix,
                ] : null,
            ];
        })->values()->all();
    }

    public function probationEndingCount(Carbon $today, Carbon $end, array $allowedStatuses): int
    {
        return Employee::query()
            ->whereNotNull('regularization_date')
            ->whereBetween('regularization_date', [$today->toDateString(), $end->toDateString()])
            ->when(count($allowedStatuses) > 0, fn ($q) => $q->whereIn('status', $allowedStatuses))
            ->count();
    }

    public function probationEndingSoonPayload(Carbon $today, Carbon $end, array $allowedStatuses, int $limit = 5): array
    {
        return Employee::query()
            ->from('employees')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.department_id')
            ->select([
                'employees.employee_id',
                'employees.employee_code',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.status',
                'employees.regularization_date',
                'departments.name as department_name',
            ])
            ->whereNotNull('employees.regularization_date')
            ->whereBetween('employees.regularization_date', [$today->toDateString(), $end->toDateString()])
            ->when(count($allowedStatuses) > 0, fn ($q) => $q->whereIn('employees.status', $allowedStatuses))
            ->orderBy('employees.regularization_date', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($today) {
                $fullName = trim(implode(' ', array_filter([
                    (string) ($row->first_name ?? ''),
                    (string) ($row->middle_name ?? ''),
                    (string) ($row->last_name ?? ''),
                    (string) ($row->suffix ?? ''),
                ])));

                $daysRemaining = $row->regularization_date
                    ? $today->diffInDays($row->regularization_date, false)
                    : null;

                return [
                    'employee_id' => $row->employee_id,
                    'employee_code' => $row->employee_code,
                    'full_name' => $fullName,
                    'department' => $row->department_name ?? null,
                    'regularization_date' => $row->regularization_date?->toDateString(),
                    'days_remaining' => $daysRemaining,
                    'status' => $row->status,
                ];
            })
            ->values()
            ->all();
    }

    public function pendingLeaveApprovalsCount(): int
    {
        return LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();
    }

    public function pendingLeaveApprovalsTopPayload(int $limit = 5): array
    {
        return LeaveRequest::query()
            ->select(['id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'is_half_day', 'half_day_part', 'status', 'created_at'])
            ->with([
                'employee:employee_id,employee_code,first_name,middle_name,last_name,suffix',
                'leaveType:id,name,code',
            ])
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (LeaveRequest $lr) {
                return [
                    'id' => $lr->id,
                    'start_date' => $lr->start_date?->format('Y-m-d'),
                    'end_date' => $lr->end_date?->format('Y-m-d'),
                    'is_half_day' => (bool) $lr->is_half_day,
                    'half_day_part' => $lr->half_day_part,
                    'status' => $lr->status,
                    'total_days' => $lr->total_days,
                    'employee' => $lr->employee ? $lr->employee->only(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix']) : null,
                    'leave_type' => $lr->leaveType ? $lr->leaveType->only(['id', 'name', 'code']) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function upcomingApprovedLeavesTopPayload(Carbon $today, Carbon $windowEnd, int $limit = 5): array
    {
        return LeaveRequest::query()
            ->select(['id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'is_half_day', 'half_day_part', 'status'])
            ->with([
                'employee:employee_id,employee_code,first_name,middle_name,last_name,suffix',
                'leaveType:id,name,code',
            ])
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $windowEnd->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get()
            ->map(function (LeaveRequest $lr) {
                return [
                    'id' => $lr->id,
                    'start_date' => $lr->start_date?->format('Y-m-d'),
                    'end_date' => $lr->end_date?->format('Y-m-d'),
                    'is_half_day' => (bool) $lr->is_half_day,
                    'half_day_part' => $lr->half_day_part,
                    'status' => $lr->status,
                    'total_days' => $lr->total_days,
                    'employee' => $lr->employee ? $lr->employee->only(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix']) : null,
                    'leave_type' => $lr->leaveType ? $lr->leaveType->only(['id', 'name', 'code']) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function openIncidentsCount(): int
    {
        return EmployeeIncident::query()
            ->whereIn('status', [EmployeeIncident::STATUS_OPEN, EmployeeIncident::STATUS_UNDER_REVIEW])
            ->count();
    }

    public function openIncidentsTopPayload(int $limit = 5): array
    {
        return EmployeeIncident::query()
            ->select(['id', 'employee_id', 'category', 'incident_date', 'status', 'follow_up_date', 'created_at'])
            ->with([
                'employee:employee_id,employee_code,first_name,middle_name,last_name,suffix',
            ])
            ->whereIn('status', [EmployeeIncident::STATUS_OPEN, EmployeeIncident::STATUS_UNDER_REVIEW])
            ->orderByRaw("FIELD(status, 'OPEN', 'UNDER_REVIEW')")
            ->orderByDesc('incident_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (EmployeeIncident $incident) {
                return [
                    'id' => $incident->id,
                    'category' => $incident->category,
                    'incident_date' => $incident->incident_date?->toDateString(),
                    'status' => $incident->status,
                    'follow_up_date' => $incident->follow_up_date?->toDateString(),
                    'created_at' => $incident->created_at?->format('Y-m-d H:i:s'),
                    'employee' => $incident->employee ? $incident->employee->only([
                        'employee_id',
                        'employee_code',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'suffix',
                    ]) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function attendanceUnmarkedTodayCount(Carbon $day): int
    {
        $employeeIds = Employee::query()->pluck('employee_id')->map(fn ($v) => (int) $v)->values()->all();
        if (count($employeeIds) === 0) {
            return 0;
        }

        $onLeaveIds = LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->pluck('employee_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $markedIds = AttendanceRecord::query()
            ->whereDate('date', $day->toDateString())
            ->whereIn('employee_id', $employeeIds)
            ->pluck('employee_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $exclude = array_unique(array_merge($onLeaveIds, $markedIds));

        return Employee::query()
            ->when(count($exclude) > 0, fn ($q) => $q->whereNotIn('employee_id', $exclude))
            ->count();
    }

    public function attendanceUnmarkedTodayTopPayload(Carbon $day, int $limit = 5): array
    {
        $employeeIds = Employee::query()->pluck('employee_id')->map(fn ($v) => (int) $v)->values()->all();
        if (count($employeeIds) === 0) {
            return [];
        }

        $onLeaveIds = LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->pluck('employee_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $markedIds = AttendanceRecord::query()
            ->whereDate('date', $day->toDateString())
            ->whereIn('employee_id', $employeeIds)
            ->pluck('employee_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $exclude = array_unique(array_merge($onLeaveIds, $markedIds));

        return Employee::query()
            ->select(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix'])
            ->when(count($exclude) > 0, fn ($q) => $q->whereNotIn('employee_id', $exclude))
            ->orderBy('employee_code', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn (Employee $e) => $e->only(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix']))
            ->values()
            ->all();
    }
}
