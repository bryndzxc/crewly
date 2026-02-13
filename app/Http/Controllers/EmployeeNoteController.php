<?php

namespace App\Http\Controllers;

use App\DTO\EmployeeNoteData;
use App\Http\Requests\StoreEmployeeNoteRequest;
use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Services\EmployeeRelationService;
use Illuminate\Http\RedirectResponse;

class EmployeeNoteController extends Controller
{
    public function __construct(private readonly EmployeeRelationService $employeeRelationService)
    {
    }

    public function store(StoreEmployeeNoteRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();

        $files = $request->file('attachments', []);
        $files = is_array($files) ? $files : [];

        $this->employeeRelationService->addNote(
            $employee,
            EmployeeNoteData::fromArray($validated),
            $files,
            $request->user()?->id
        );

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Note added successfully.');
    }

    public function destroy(Employee $employee, EmployeeNote $note): RedirectResponse
    {
        if ((int) $note->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $this->employeeRelationService->deleteNote($employee, $note);

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Note deleted successfully.');
    }
}
