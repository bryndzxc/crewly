<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Roles/Index', $this->roleService->index($request));
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'key')->whereNull('deleted_at')],
        ]);

        $this->roleService->create($validated);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Roles/Edit', [
            'role' => $role->only(['id', 'key', 'name']),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'key')->ignore($role->id)->whereNull('deleted_at')],
        ]);

        $this->roleService->update($role, $validated);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roleService->delete($role);

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

}
