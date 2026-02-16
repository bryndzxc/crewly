<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Services\EmployeeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyProfileController extends Controller
{
    public function __construct(
        private readonly EmployeeResolver $employeeResolver,
    ) {}

    public function show(Request $request): Response
    {
        $this->authorize('access-my-portal');

        $employee = $this->employeeResolver->requireCurrent($request->user());
        $employee->load(['department:department_id,name,code']);

        $name = collect([
            $employee->first_name,
            $employee->middle_name,
            $employee->last_name,
            $employee->suffix,
        ])->map(fn ($v) => trim((string) $v))->filter()->implode(' ');

        return Inertia::render('My/Profile', [
            'employee' => [
                'employee_id' => (int) $employee->employee_id,
                'employee_code' => (string) $employee->employee_code,
                'name' => $name,
                'email' => (string) $employee->email,
                'department' => $employee->department ? $employee->department->only(['department_id', 'name', 'code']) : null,
                'position_title' => (string) ($employee->position_title ?? ''),
                'employment_type' => (string) ($employee->employment_type ?? ''),
                'status' => (string) ($employee->status ?? ''),
                'date_hired' => $employee->date_hired?->format('Y-m-d'),
                'regularization_date' => $employee->regularization_date?->format('Y-m-d'),
            ],
        ]);
    }
}
