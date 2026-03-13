<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveEmployeeCompensationRequest;
use App\Models\Employee;
use App\Services\EmployeePayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeCompensationController extends Controller
{
    public function __construct(private readonly EmployeePayrollService $employeePayrollService)
    {
    }

    public function show(Request $request, Employee $employee): JsonResponse|RedirectResponse
    {
        $compensation = $employee->compensation()->first();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $compensation ? [
                    'id' => $compensation->id,
                    'employee_id' => $compensation->employee_id,
                    'salary_type' => $compensation->salary_type,
                    'base_salary' => $compensation->base_salary,
                    'pay_frequency' => $compensation->pay_frequency,
                    'effective_date' => $compensation->effective_date?->format('Y-m-d'),
                    'notes' => $compensation->notes,
                    'created_at' => $compensation->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $compensation->updated_at?->format('Y-m-d H:i:s'),
                ] : null,
            ]);
        }

        return redirect()->route('employees.show', $employee->employee_id);
    }

    public function store(SaveEmployeeCompensationRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeePayrollService->createCompensation(
            $employee,
            $request->validated(),
            $request->user()?->id
        );

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Compensation saved successfully.');
    }

    public function update(SaveEmployeeCompensationRequest $request, Employee $employee): RedirectResponse
    {
        $compensation = $employee->compensation()->first();

        if (!$compensation) {
            abort(404);
        }

        $this->employeePayrollService->updateCompensation(
            $employee,
            $compensation,
            $request->validated(),
            $request->user()?->id
        );

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Compensation updated successfully.');
    }
}