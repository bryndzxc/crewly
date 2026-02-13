<?php

namespace App\Http\Controllers;

use App\DTO\EmployeeIncidentData;
use App\DTO\EmployeeIncidentUpdateData;
use App\Http\Requests\StoreEmployeeIncidentRequest;
use App\Http\Requests\UpdateEmployeeIncidentRequest;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Services\EmployeeRelationService;
use Illuminate\Http\RedirectResponse;

class EmployeeIncidentController extends Controller
{
    public function __construct(private readonly EmployeeRelationService $employeeRelationService)
    {
    }

    public function store(StoreEmployeeIncidentRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();

        $files = $request->file('attachments', []);
        $files = is_array($files) ? $files : [];

        $this->employeeRelationService->createIncident(
            $employee,
            EmployeeIncidentData::fromArray($validated),
            $files,
            $request->user()?->id
        );

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Incident created successfully.');
    }

    public function update(UpdateEmployeeIncidentRequest $request, Employee $employee, EmployeeIncident $incident): RedirectResponse
    {
        if ((int) $incident->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $validated = $request->validated();

        $this->employeeRelationService->updateIncident(
            $employee,
            $incident,
            EmployeeIncidentUpdateData::fromArray($validated),
        );

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Incident updated successfully.');
    }

    public function destroy(Employee $employee, EmployeeIncident $incident): RedirectResponse
    {
        if ((int) $incident->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $this->employeeRelationService->deleteIncident($employee, $incident);

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Incident deleted successfully.');
    }
}
