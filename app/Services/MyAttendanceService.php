<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;

class MyAttendanceService
{
    /**
     * @return array{date:string,record:array<string,mixed>|null,metrics:array<string,int|null>,schedule:array{schedule_start:string,schedule_end:string,break_minutes:int,grace_minutes:int}}
     */
    public function daily(int $employeeId, string $date, ?Company $company = null): array
    {
        $date = Carbon::parse($date)->toDateString();

        $record = AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->first(['id', 'employee_id', 'date', 'status', 'time_in', 'time_out', 'remarks', 'updated_at']);

        $schedule = $this->scheduleConfig($company);

        $metrics = $this->computeMetrics(
            date: $date,
            status: $record?->status,
            timeIn: $record?->time_in,
            timeOut: $record?->time_out,
            schedule: $schedule,
        );

        return [
            'date' => $date,
            'record' => $record ? [
                'id' => (int) $record->id,
                'date' => $record->date?->toDateString(),
                'status' => (string) ($record->status ?? ''),
                'time_in' => $record->time_in,
                'time_out' => $record->time_out,
                'remarks' => $record->remarks,
                'updated_at' => $record->updated_at?->format('Y-m-d H:i:s'),
            ] : null,
            'metrics' => $metrics,
            'schedule' => $schedule,
        ];
    }

    /**
     * @return array{month:string,days:array<int,array{date:string,day:int,dow:string}>,cells:array<int,array{date:string,code:string}>,totals:array{present:int,absent:int,leave:int}}
     */
    public function monthly(int $employeeId, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'date', 'status'])
            ->keyBy(fn (AttendanceRecord $r) => (string) $r->date?->toDateString());

        $leaves = LeaveRequest::query()
            ->select(['id', 'start_date', 'end_date', 'is_half_day', 'half_day_part', 'status'])
            ->where('employee_id', $employeeId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('start_date', 'asc')
            ->get();

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $cells = [];
        $totals = ['present' => 0, 'absent' => 0, 'leave' => 0];

        foreach ($days as $day) {
            $rec = $records->get($day);
            $hasLeave = $this->hasLeaveOnDay($leaves, $day);

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

            $cells[] = ['date' => $day, 'code' => $code];
        }

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
            'cells' => $cells,
            'totals' => [
                'present' => (int) $totals['present'],
                'absent' => (int) $totals['absent'],
                'leave' => (int) $totals['leave'],
            ],
        ];
    }

    /**
     * @return array{schedule_start:string,schedule_end:string,break_minutes:int,grace_minutes:int}
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
     * @param \Illuminate\Support\Collection<int, LeaveRequest> $leaves
     */
    private function hasLeaveOnDay($leaves, string $day): bool
    {
        foreach ($leaves as $lr) {
            $s = (string) ($lr->start_date?->toDateString() ?? '');
            $e = (string) ($lr->end_date?->toDateString() ?? '');
            if ($s !== '' && $e !== '' && $day >= $s && $day <= $e) {
                return true;
            }
        }

        return false;
    }

    /**
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

        $grace = (int) ($schedule['grace_minutes'] ?? 0);
        $late = max(0, $start->addMinutes($grace)->diffInMinutes($in, false));
        if ($late < 0) {
            $late = 0;
        }

        $scheduledMinutes = max(0, $start->diffInMinutes($end) - max(0, (int) $schedule['break_minutes']));
        $undertime = max(0, $scheduledMinutes - $worked);

        $overtime = max(0, $worked - $scheduledMinutes);

        return [
            'worked_minutes' => (int) $worked,
            'late_minutes' => (int) $late,
            'undertime_minutes' => (int) $undertime,
            'overtime_minutes' => (int) $overtime,
        ];
    }
}
