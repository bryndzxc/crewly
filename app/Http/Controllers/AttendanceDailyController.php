<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertAttendanceRecordRequest;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
