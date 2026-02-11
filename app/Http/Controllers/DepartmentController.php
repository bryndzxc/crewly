<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    private DepartmentService $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Departments/Index', $this->departmentService->index($request));
    }

    public function create(): Response
    {
        return Inertia::render('Departments/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-]+$/', Rule::unique('departments', 'code')->whereNull('deleted_at')],
        ]);

        $this->departmentService->create($validated);

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('Departments/Edit', [
            'department' => $department->only(['department_id', 'name', 'code']),
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-]+$/', Rule::unique('departments', 'code')->ignore($department->department_id, 'department_id')->whereNull('deleted_at')],
        ]);

        $this->departmentService->update($department, $validated);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->departmentService->delete($department);

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }
}
