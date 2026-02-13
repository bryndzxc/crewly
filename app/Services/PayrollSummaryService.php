<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayrollSummaryService extends Service
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, meta: array<string, mixed>, totals: array<string, mixed>}
     */
    public function generate(Carbon $from, Carbon $to, User $user): array
    {
        $fromDate = $from->copy()->startOfDay()->toDateString();
        $toDate = $to->copy()->startOfDay()->toDateString();

        $schedule = $this->scheduleConfig();

        $employees = $this->employeesForUser($user);
        $employeeIds = $employees->pluck('employee_id')->map(fn ($v) => (int) $v)->values()->all();

        $attendance = $this->attendanceRecords($employeeIds, $fromDate, $toDate);
        $leaves = $this->approvedLeaveDaysMap($employeeIds, $fromDate, $toDate);

        $rows = [];
        $totals = [
            'employees' => 0,
            'present_days' => 0,
            'absent_days' => 0,
            'on_leave_days' => 0,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
        ];

        foreach ($employees as $employee) {
            $employeeId = (int) $employee->employee_id;

            /** @var Collection<int, AttendanceRecord> $employeeAttendance */
            $employeeAttendance = $attendance->get($employeeId) ?? collect();

            $presentDays = 0;
            $absentDays = 0;

            $workedMinutes = 0;
            $lateMinutes = 0;
            $undertimeMinutes = 0;
            $overtimeMinutes = 0;

            $daysWithAttendanceStatus = [];

            foreach ($employeeAttendance as $record) {
                $day = (string) ($record->date?->toDateString() ?? '');
                if ($day === '') {
                    continue;
                }

                $status = $record->status;

                if ($status === AttendanceRecord::STATUS_PRESENT) {
                    $presentDays++;
                    $daysWithAttendanceStatus[$day] = true;

                    $metrics = $this->computeMetrics(
                        $day,
                        $record->status,
                        $record->time_in,
                        $record->time_out,
                        $schedule,
                    );

                    $workedMinutes += (int) ($metrics['worked_minutes'] ?? 0);
                    $lateMinutes += (int) ($metrics['late_minutes'] ?? 0);
                    $undertimeMinutes += (int) ($metrics['undertime_minutes'] ?? 0);
                    $overtimeMinutes += (int) ($metrics['overtime_minutes'] ?? 0);
                }

                if ($status === AttendanceRecord::STATUS_ABSENT) {
                    $absentDays++;
                    $daysWithAttendanceStatus[$day] = true;
                }
            }

            $leaveDaysSet = $leaves[$employeeId] ?? [];
            $onLeaveDays = 0;
            foreach ($leaveDaysSet as $day => $_) {
                // Only count leave if attendance is not explicitly marked for that day.
                if (!isset($daysWithAttendanceStatus[$day])) {
                    $onLeaveDays++;
                }
            }

            $workedHours = round($workedMinutes / 60, 2);

            $rows[] = [
                'employee_id' => $employeeId,
                'employee_code' => $employee->employee_code,
                'employee_name' => $this->fullName($employee),
                'department' => $employee->department_name,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'on_leave_days' => $onLeaveDays,
                'worked_hours' => $workedHours,
                'worked_minutes' => $workedMinutes,
                'late_minutes' => $lateMinutes,
                'undertime_minutes' => $undertimeMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ];

            $totals['employees']++;
            $totals['present_days'] += $presentDays;
            $totals['absent_days'] += $absentDays;
            $totals['on_leave_days'] += $onLeaveDays;
            $totals['worked_minutes'] += $workedMinutes;
            $totals['late_minutes'] += $lateMinutes;
            $totals['undertime_minutes'] += $undertimeMinutes;
            $totals['overtime_minutes'] += $overtimeMinutes;
        }

        $totals['worked_hours'] = round(((int) $totals['worked_minutes']) / 60, 2);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'meta' => [
                'from' => $fromDate,
                'to' => $toDate,
                'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function employeesForUser(User $user): Collection
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
                'employees.department_id',
                'departments.name as department_name',
            ])
            ->orderBy('employees.employee_code', 'asc')
            ->get();
    }

    /**
     * @param array<int, int> $employeeIds
     * @return Collection<int, Collection<int, AttendanceRecord>>
     */
    private function attendanceRecords(array $employeeIds, string $fromDate, string $toDate): Collection
    {
        if (count($employeeIds) === 0) {
            return collect();
        }

        return AttendanceRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'asc')
            ->get([
                'id',
                'employee_id',
                'date',
                'status',
                'time_in',
                'time_out',
            ])
            ->groupBy('employee_id');
    }

    /**
     * @param array<int, int> $employeeIds
     * @return array<int, array<string, bool>>
     */
    private function approvedLeaveDaysMap(array $employeeIds, string $fromDate, string $toDate): array
    {
        if (count($employeeIds) === 0) {
            return [];
        }

        $items = LeaveRequest::query()
            ->select(['id', 'employee_id', 'start_date', 'end_date', 'is_half_day'])
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $toDate)
            ->whereDate('end_date', '>=', $fromDate)
            ->get();

        $map = [];

        foreach ($items as $lr) {
            $employeeId = (int) $lr->employee_id;
            $start = Carbon::parse($lr->start_date)->startOfDay();
            $end = Carbon::parse($lr->end_date)->startOfDay();

            if ($start->toDateString() < $fromDate) {
                $start = Carbon::parse($fromDate);
            }
            if ($end->toDateString() > $toDate) {
                $end = Carbon::parse($toDate);
            }

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $day = $cursor->toDateString();
                $map[$employeeId] ??= [];
                $map[$employeeId][$day] = true;
                $cursor->addDay();
            }
        }

        return $map;
    }

    /**
     * @return array{schedule_start:string, schedule_end:string, break_minutes:int, grace_minutes:int}
     */
    private function scheduleConfig(): array
    {
        return [
            'schedule_start' => (string) config('crewly.attendance.schedule_start', '09:00'),
            'schedule_end' => (string) config('crewly.attendance.schedule_end', '18:00'),
            'break_minutes' => (int) config('crewly.attendance.break_minutes', 60),
            'grace_minutes' => (int) config('crewly.attendance.grace_minutes', 0),
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
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        if (!$timeIn || !$timeOut) {
            return [
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        $d = Carbon::parse($date);
        $in = Carbon::parse($d->toDateString() . ' ' . (string) $timeIn);
        $out = Carbon::parse($d->toDateString() . ' ' . (string) $timeOut);
        if ($out->lessThan($in)) {
            return [
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
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

    private function fullName(object $employee): string
    {
        $parts = array_filter([
            trim((string) ($employee->first_name ?? '')),
            trim((string) ($employee->middle_name ?? '')),
            trim((string) ($employee->last_name ?? '')),
            trim((string) ($employee->suffix ?? '')),
        ]);

        return trim(implode(' ', $parts));
    }
}
