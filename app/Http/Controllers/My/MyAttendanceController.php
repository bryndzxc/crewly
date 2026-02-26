<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Services\EmployeeResolver;
use App\Services\MyAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MyAttendanceController extends Controller
{
    public function __construct(
        private readonly EmployeeResolver $employeeResolver,
        private readonly MyAttendanceService $myAttendanceService,
    ) {}

    public function daily(Request $request): Response
    {
        $this->authorize('access-my-portal');

        $employee = $this->employeeResolver->requireCurrent($request->user());
        $date = (string) $request->query('date', Carbon::today()->toDateString());

        return Inertia::render('My/Attendance/Daily', $this->myAttendanceService->daily((int) $employee->employee_id, $date, $request->user()?->company));
    }

    public function monthly(Request $request): Response
    {
        $this->authorize('access-my-portal');

        $employee = $this->employeeResolver->requireCurrent($request->user());
        $month = (string) $request->query('month', Carbon::today()->format('Y-m'));

        return Inertia::render('My/Attendance/Monthly', $this->myAttendanceService->monthly((int) $employee->employee_id, $month));
    }
}
