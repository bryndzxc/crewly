<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function index(Request $request): Response
    {
        $result = $this->userService->index($request);

        return Inertia::render('Users/Index', [
            'users' => $result['users'],
            'filters' => $result['filters'],
            'roles' => $this->userService->rolesForSelect(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => $this->userService->rolesForSelect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'key')],
        ]);

        $actor = $request->user();
        abort_unless($actor && $actor->company_id, 403);
        $validated['company_id'] = (int) $actor->company_id;

        $this->userService->create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        $actor = request()->user();
        abort_unless($actor && (int) $user->company_id === (int) $actor->company_id, 404);

        return Inertia::render('Users/Edit', [
            'userRecord' => $user->only(['id', 'name', 'email', 'role']),
            'roles' => $this->userService->rolesForSelect(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && (int) $user->company_id === (int) $actor->company_id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at')],
            'role' => ['required', 'string', Rule::exists('roles', 'key')],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $this->userService->update($user, $validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $actor = request()->user();
        abort_unless($actor && (int) $user->company_id === (int) $actor->company_id, 404);

        $this->userService->delete($user);

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

}
