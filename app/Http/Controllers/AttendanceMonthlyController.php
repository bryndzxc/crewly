<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceMonthlyController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('access-attendance');

        return Inertia::render('Attendance/Monthly', $this->attendanceService->monthly($request));
    }
}
