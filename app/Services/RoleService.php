<?php

namespace App\Services;

use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService extends Service
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return [
            'roles' => Role::selectedFields()
                ->searchable($q)
                ->sortable()
                ->pagination($perPage, ['id', 'key', 'name']),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ];
    }

    public function create(array $validated): Role
    {
        return DB::transaction(function () use ($validated) {
            
            return $this->roleRepository->createRole($validated);
        });
    }

    public function update(Role $role, array $validated): Role
    {
        return DB::transaction(function () use ($role, $validated) {
            return $this->roleRepository->updateRole($role, $validated);
        });
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $this->roleRepository->deleteRole($role);
        });
    }
}
