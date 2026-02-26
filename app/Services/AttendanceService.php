<?php

namespace App\Services;

use App\DTO\AttendanceRecordUpsertData;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Repositories\AttendanceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AttendanceService extends Service
{
    public function __construct(
        private readonly AttendanceRepository $attendanceRepository,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function daily(Request $request): array
    {
        $date = (string) $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($date)->toDateString();

        $user = $request->user();
        $canManage = $user ? Gate::forUser($user)->check('manage-attendance') : false;

        $employees = Employee::query()
            ->orderBy('employee_code')
            ->get([
                'employee_id',
                'employee_code',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'department_id',
                'status',
            ]);

        $employeeIds = $employees->pluck('employee_id')->map(fn ($v) => (int) $v)->values()->all();

        $records = $this->attendanceRepository->recordsForDate($employeeIds, $date);
        $leaveMap = $this->approvedLeaveMap($employeeIds, $date, $date);

        $schedule = $this->scheduleConfig($user?->company);

        $rows = $employees->map(function (Employee $employee) use ($records, $leaveMap, $date, $schedule) {
            $record = $records->get((int) $employee->employee_id);
            $leave = $leaveMap[(int) $employee->employee_id] ?? null;

            $computed = $this->computeMetrics(
                $date,
                $record?->status,
                $record?->time_in,
                $record?->time_out,
                $schedule,
            );

            return [
                'employee' => $employee->only([
                    'employee_id',
                    'employee_code',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'suffix',
                    'department_id',
                    'status',
                ]),
                'record' => $record ? [
                    'id' => $record->id,
                    'date' => $record->date?->toDateString(),
                    'status' => $record->status,
                    'time_in' => $record->time_in,
                    'time_out' => $record->time_out,
                    'remarks' => $record->remarks,
                    'updated_at' => $record->updated_at?->format('Y-m-d H:i:s'),
                ] : null,
                'leave' => $leave,
                'metrics' => $computed,
            ];
        })->values()->all();

        return [
            'date' => $date,
            'rows' => $rows,
            'actions' => [
                'can_manage' => $canManage,
            ],
            'schedule' => $schedule,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function upsertRow(int $employeeId, array $validated, ?int $userId): AttendanceRecord
    {
        $dto = AttendanceRecordUpsertData::fromArray($employeeId, $validated);

        $existing = AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->where('date', $dto->date)
            ->first();

        if ($dto->status !== AttendanceRecord::STATUS_PRESENT) {
            $dto = new AttendanceRecordUpsertData(
                employeeId: $dto->employeeId,
                date: $dto->date,
                status: $dto->status,
                timeIn: null,
                timeOut: null,
                remarks: $dto->remarks,
            );
        }

        $record = $this->attendanceRepository->upsert($dto, $userId);

        $this->activityLogService->log(
            'attendance_upsert',
            $record,
            [
                'employee_id' => $employeeId,
                'date' => $dto->date,
                'status' => $dto->status,
            ],
            'Attendance updated',
        );

        app(\App\Services\AuditLogger::class)->log(
            'attendance.updated',
            $record,
            $existing ? $existing->only(['status', 'time_in', 'time_out', 'remarks']) : [],
            $record->only(['status', 'time_in', 'time_out', 'remarks']),
            [
                'employee_id' => $employeeId,
                'date' => $dto->date,
            ],
            'Attendance updated.'
        );

        return $record;
    }

    public function monthly(Request $request): array
    {
        $month = (string) $request->query('month', Carbon::today()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $employees = Employee::query()
            ->orderBy('employee_code')
            ->get([
                'employee_id',
                'employee_code',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'department_id',
                'status',
            ]);

        $employeeIds = $employees->pluck('employee_id')->map(fn ($v) => (int) $v)->values()->all();

        $records = AttendanceRecord::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get([
                'id',
                'employee_id',
                'date',
                'status',
                'time_in',
                'time_out',
            ])
            ->groupBy('employee_id');

        $leaveMap = $this->approvedLeaveMap($employeeIds, $start->toDateString(), $end->toDateString());

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $rows = $employees->map(function (Employee $employee) use ($records, $leaveMap, $days) {
            $employeeId = (int) $employee->employee_id;
            $employeeRecords = ($records->get($employeeId) ?? collect())->keyBy(fn ($r) => (string) $r->date?->toDateString());

            $dayCells = [];
            $totals = [
                'present' => 0,
                'absent' => 0,
                'leave' => 0,
            ];

            foreach ($days as $day) {
                $rec = $employeeRecords->get($day);
                $hasLeave = isset($leaveMap[$employeeId]) && $this->employeeHasLeaveOnDay($leaveMap[$employeeId], $day);

                $code = '—';
                if ($rec?->status === AttendanceRecord::STATUS_PRESENT) {
                    $code = 'P';
                    $totals['present']++;
                } elseif ($rec?->status === AttendanceRecord::STATUS_ABSENT) {
                    $code = 'A';
                    $totals['absent']++;
                } elseif ($hasLeave) {
                    $code = 'L';
                    $totals['leave']++;
                }

                $dayCells[] = [
                    'date' => $day,
                    'code' => $code,
                ];
            }

            return [
                'employee' => $employee->only([
                    'employee_id',
                    'employee_code',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'suffix',
                    'department_id',
                    'status',
                ]),
                'days' => $dayCells,
                'totals' => $totals,
            ];
        })->values()->all();

        return [
            'month' => $start->format('Y-m'),
            'days' => array_map(function (string $day) {
                $c = Carbon::parse($day);
                return [
                    'date' => $day,
                    'day' => (int) $c->format('j'),
                    'dow' => $c->format('D'),
                ];
            }, $days),
            'rows' => $rows,
        ];
    }

    /**
     * @param array<int, int> $employeeIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function approvedLeaveMap(array $employeeIds, string $startDate, string $endDate): array
    {
        if (count($employeeIds) === 0) {
            return [];
        }

        $leaves = LeaveRequest::query()
            ->select(['id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'is_half_day', 'half_day_part', 'status'])
            ->with(['leaveType:id,name,code'])
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->orderBy('start_date', 'asc')
            ->get();

        $map = [];
        foreach ($leaves as $lr) {
            $eid = (int) $lr->employee_id;
            $map[$eid] ??= [];
            $map[$eid][] = [
                'id' => $lr->id,
                'start_date' => $lr->start_date?->toDateString(),
                'end_date' => $lr->end_date?->toDateString(),
                'is_half_day' => (bool) $lr->is_half_day,
                'half_day_part' => $lr->half_day_part,
                'leave_type' => $lr->leaveType ? $lr->leaveType->only(['id', 'name', 'code']) : null,
            ];
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $leaves
     */
    private function employeeHasLeaveOnDay(array $leaves, string $day): bool
    {
        foreach ($leaves as $leave) {
            $s = (string) ($leave['start_date'] ?? '');
            $e = (string) ($leave['end_date'] ?? '');
            if ($s !== '' && $e !== '' && $day >= $s && $day <= $e) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{schedule_start:string, schedule_end:string, break_minutes:int, grace_minutes:int}
     */
    private function scheduleConfig(?Company $company): array
    {
        $fallback = [
            'schedule_start' => (string) config('crewly.attendance.schedule_start', '09:00'),
            'schedule_end' => (string) config('crewly.attendance.schedule_end', '18:00'),
            'break_minutes' => (int) config('crewly.attendance.break_minutes', 60),
            'grace_minutes' => (int) config('crewly.attendance.grace_minutes', 0),
        ];

        if (!$company) {
            return $fallback;
        }

        return [
            'schedule_start' => (string) ($company->attendance_schedule_start ?: $fallback['schedule_start']),
            'schedule_end' => (string) ($company->attendance_schedule_end ?: $fallback['schedule_end']),
            'break_minutes' => (int) (($company->attendance_break_minutes ?? $fallback['break_minutes'])),
            'grace_minutes' => (int) (($company->attendance_grace_minutes ?? $fallback['grace_minutes'])),
        ];
    }

    /**
     * @param mixed $timeIn
     * @param mixed $timeOut
     * @param array{schedule_start:string, schedule_end:string, break_minutes:int, grace_minutes:int} $schedule
     * @return array<string, int|null>
     */
    private function computeMetrics(string $date, ?string $status, $timeIn, $timeOut, array $schedule): array
    {
        if ($status !== AttendanceRecord::STATUS_PRESENT) {
            return [
                'worked_minutes' => null,
                'late_minutes' => null,
                'undertime_minutes' => null,
                'overtime_minutes' => null,
            ];
        }

        if (!$timeIn || !$timeOut) {
            return [
                'worked_minutes' => null,
                'late_minutes' => null,
                'undertime_minutes' => null,
                'overtime_minutes' => null,
            ];
        }

        $d = Carbon::parse($date);
        $in = Carbon::parse($d->toDateString() . ' ' . (string) $timeIn);
        $out = Carbon::parse($d->toDateString() . ' ' . (string) $timeOut);
        if ($out->lessThan($in)) {
            return [
                'worked_minutes' => null,
                'late_minutes' => null,
                'undertime_minutes' => null,
                'overtime_minutes' => null,
            ];
        }

        $start = Carbon::parse($d->toDateString() . ' ' . $schedule['schedule_start']);
        $end = Carbon::parse($d->toDateString() . ' ' . $schedule['schedule_end']);

        $worked = max(0, $in->diffInMinutes($out) - max(0, (int) $schedule['break_minutes']));

        $grace = max(0, (int) $schedule['grace_minutes']);

        $late = 0;
        $lateThreshold = $start->copy()->addMinutes($grace);
        if ($in->greaterThan($lateThreshold)) {
            $late = max(0, $start->diffInMinutes($in) - $grace);
        }

        $undertime = 0;
        if ($out->lessThan($end)) {
            $undertime = $out->diffInMinutes($end);
        }

        $overtime = 0;
        if ($out->greaterThan($end)) {
            $overtime = $end->diffInMinutes($out);
        }

        return [
            'worked_minutes' => $worked,
            'late_minutes' => $late,
            'undertime_minutes' => $undertime,
            'overtime_minutes' => $overtime,
        ];
    }
}
