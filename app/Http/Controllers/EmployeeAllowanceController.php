<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveEmployeeAllowanceRequest;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Services\EmployeePayrollService;
use Illuminate\Http\RedirectResponse;

class EmployeeAllowanceController extends Controller
{
    public function __construct(private readonly EmployeePayrollService $employeePayrollService)
    {
    }

    public function store(SaveEmployeeAllowanceRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeePayrollService->createAllowance($employee, $request->validated());

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Allowance added successfully.');
    }

    public function update(
        SaveEmployeeAllowanceRequest $request,
        Employee $employee,
        EmployeeAllowance $allowance
    ): RedirectResponse {
        if ((int) $allowance->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $this->employeePayrollService->updateAllowance($employee, $allowance, $request->validated());

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Allowance updated successfully.');
    }

    public function destroy(Employee $employee, EmployeeAllowance $allowance): RedirectResponse
    {
        if ((int) $allowance->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $this->employeePayrollService->deleteAllowance($allowance);

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Allowance deleted successfully.');
    }
}