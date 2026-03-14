<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeCompensation;
use App\Models\LeaveRequest;
use App\Services\EmployeeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeDashboardController extends Controller
{
    public function __construct(
        private readonly EmployeeResolver $employeeResolver,
    ) {}

    public function show(Request $request): Response
    {
        $this->authorize('access-my-portal');

        $employee = $this->employeeResolver->requireCurrent($request->user());
        $employee->load(['department:department_id,name,code']);

        $employeeName = collect([
            $employee->first_name,
            $employee->middle_name,
            $employee->last_name,
            $employee->suffix,
        ])->map(fn ($v) => trim((string) $v))->filter()->implode(' ');

        $leaveCounts = LeaveRequest::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [(string) $r->status => (int) ($r->c ?? 0)]);

        $recentLeave = LeaveRequest::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->with(['leaveType:id,name,code'])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (LeaveRequest $lr) => [
                'id' => (int) $lr->id,
                'start_date' => $lr->start_date?->format('Y-m-d'),
                'end_date' => $lr->end_date?->format('Y-m-d'),
                'status' => (string) $lr->status,
                'leave_type' => $lr->leaveType ? $lr->leaveType->only(['id', 'name', 'code']) : null,
            ])->values()->all();

        $attendance = AttendanceRecord::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'date', 'status', 'time_in', 'time_out'])
            ->map(fn (AttendanceRecord $r) => [
                'id' => (int) $r->id,
                'date' => $r->date?->toDateString(),
                'status' => (string) $r->status,
                'time_in' => $r->time_in,
                'time_out' => $r->time_out,
            ])->values()->all();

        $compensation = EmployeeCompensation::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->first(['salary_type', 'base_salary', 'pay_frequency', 'effective_date']);

        $allowances = EmployeeAllowance::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->orderBy('allowance_name')
            ->orderBy('id')
            ->get(['id', 'allowance_name', 'amount', 'frequency'])
            ->map(fn (EmployeeAllowance $a) => [
                'id' => (int) $a->id,
                'allowance_name' => (string) $a->allowance_name,
                'amount' => (float) ($a->amount ?? 0),
                'frequency' => (string) ($a->frequency ?? ''),
            ])->values()->all();

        $allowancesTotal = array_reduce($allowances, fn ($acc, $a) => $acc + (float) ($a['amount'] ?? 0), 0.0);

        $defaultFrom = Carbon::today()->startOfMonth()->toDateString();
        $defaultTo = Carbon::today()->toDateString();

        return Inertia::render('Employee/Dashboard', [
            'employee' => [
                'employee_id' => (int) $employee->employee_id,
                'employee_code' => (string) ($employee->employee_code ?? ''),
                'name' => $employeeName,
                'position_title' => (string) ($employee->position_title ?? ''),
                'department' => $employee->department ? $employee->department->only(['department_id', 'name', 'code']) : null,
            ],
            'leaveSummary' => [
                'pending' => (int) ($leaveCounts[LeaveRequest::STATUS_PENDING] ?? 0),
                'approved' => (int) ($leaveCounts[LeaveRequest::STATUS_APPROVED] ?? 0),
                'denied' => (int) ($leaveCounts[LeaveRequest::STATUS_DENIED] ?? 0),
                'recent' => $recentLeave,
            ],
            'attendanceHistory' => $attendance,
            'compensation' => $compensation ? [
                'salary_type' => (string) $compensation->salary_type,
                'base_salary' => (float) ($compensation->base_salary ?? 0),
                'pay_frequency' => (string) $compensation->pay_frequency,
                'effective_date' => $compensation->effective_date?->format('Y-m-d'),
            ] : null,
            'allowances' => $allowances,
            'allowancesTotal' => (float) $allowancesTotal,
            'defaults' => [
                'from' => $defaultFrom,
                'to' => $defaultTo,
            ],
        ]);
    }
}
