<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertAttendanceBulkRequest;
use App\Http\Requests\UpsertAttendanceRecordRequest;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceDailyController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('access-attendance');

        return Inertia::render('Attendance/Daily', $this->attendanceService->daily($request));
    }

    public function upsert(UpsertAttendanceRecordRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('manage-attendance');

        $validated = $request->validated();
        $this->attendanceService->upsertRow((int) $employee->employee_id, $validated, $request->user()?->id);

        return back()->with('success', 'Attendance updated.')->setStatusCode(303);
    }

    public function upsertAll(UpsertAttendanceBulkRequest $request): RedirectResponse
    {
        $this->authorize('manage-attendance');

        $user = $request->user();
        $company = $user?->company;
        abort_unless($company, 404);

        $scheduleStart = (string) ($company->attendance_schedule_start ?: config('crewly.attendance.schedule_start', ''));
        $scheduleEnd = (string) ($company->attendance_schedule_end ?: config('crewly.attendance.schedule_end', ''));
        if ($scheduleStart === '' || $scheduleEnd === '') {
            return back()->with('error', 'Please set a schedule first before updating all attendance.')->setStatusCode(303);
        }

        $validated = $request->validated();
        $date = (string) $validated['date'];
        $rows = $validated['rows'] ?? [];

        // Only update rows that actually have a status set.
        $rows = array_values(array_filter($rows, function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $status = $row['status'] ?? null;
            $status = $status !== null ? trim((string) $status) : '';
            return $status !== '';
        }));

        if (count($rows) === 0) {
            return back()->with('error', 'No attendance entries to update.')->setStatusCode(303);
        }

        $employeeIds = collect($rows)
            ->pluck('employee_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $existingEmployeeIds = Employee::query()
            ->whereIn('employee_id', $employeeIds->all())
            ->pluck('employee_id')
            ->map(fn ($v) => (int) $v)
            ->values();

        if ($existingEmployeeIds->count() !== $employeeIds->count()) {
            return back()->with('error', 'Some employees are invalid for your company.')->setStatusCode(303);
        }

        $userId = $request->user()?->id;

        DB::transaction(function () use ($rows, $date, $userId) {
            foreach ($rows as $row) {
                $employeeId = (int) ($row['employee_id'] ?? 0);
                if ($employeeId <= 0) {
                    continue;
                }

                $this->attendanceService->upsertRow(
                    $employeeId,
                    [
                        'date' => $date,
                        'status' => $row['status'] ?? null,
                        'time_in' => $row['time_in'] ?? null,
                        'time_out' => $row['time_out'] ?? null,
                        'remarks' => $row['remarks'] ?? null,
                    ],
                    $userId ? (int) $userId : null,
                );
            }
        });

        return back()->with('success', 'Attendance updated for all employees.')->setStatusCode(303);
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $this->authorize('manage-attendance');

        $user = $request->user();
        $company = $user?->company;
        abort_unless($company, 404);

        $validated = $request->validate([
            'schedule_start' => ['required', 'date_format:H:i'],
            'schedule_end' => ['required', 'date_format:H:i'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:600'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
        ]);

        $company->forceFill([
            'attendance_schedule_start' => (string) $validated['schedule_start'],
            'attendance_schedule_end' => (string) $validated['schedule_end'],
            'attendance_break_minutes' => (int) $validated['break_minutes'],
            'attendance_grace_minutes' => (int) $validated['grace_minutes'],
        ])->save();

        return back()->with('success', 'Schedule updated.')->setStatusCode(303);
    }
}
