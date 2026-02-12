<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    private EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Employees/Index', $this->employeeService->index($request));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Employees/Create', [
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request): RedirectResponse
    {
        
        $validated = $request->validated();

        $this->employeeService->create($validated);

        return to_route('employees.index')->setStatusCode(303);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Employee $employee)
    {
        if ($request->expectsJson()) {
            return new EmployeeResource($employee);
        }

        $documents = EmployeeDocument::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->orderByDesc('id')
            ->get([
                'id',
                'employee_id',
                'type',
                'original_name',
                'mime_type',
                'file_size',
                'issue_date',
                'expiry_date',
                'notes',
                'uploaded_by',
                'created_at',
            ]);

        return Inertia::render('Employees/Show', [
            'employee' => (new EmployeeResource($employee))->toArray($request),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
            'documents' => $documents,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): Response
    {
        $payload = $employee->only([
            'employee_id',
            'department_id',
            'employee_code',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'email',
            'mobile_number',
            'status',
            'position_title',
            'employment_type',
            'notes',
        ]);

        $payload['date_hired'] = $employee->date_hired?->format('Y-m-d');
        $payload['regularization_date'] = $employee->regularization_date?->format('Y-m-d');

        return Inertia::render('Employees/Edit', [
            'employee' => $payload,
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();
        $this->employeeService->update($employee, $validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $this->employeeService->delete($employee);

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
