<?php

namespace App\Services;

use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class RoleService extends Service
{
    private RoleRepository $roleRepository;
    private ActivityLogService $activityLogService;

    public function __construct(RoleRepository $roleRepository, ActivityLogService $activityLogService)
    {
        $this->roleRepository = $roleRepository;
        $this->activityLogService = $activityLogService;
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

            $role = $this->roleRepository->createRole($validated);

            $this->activityLogService->log('created', $role, [
                'attributes' => Arr::only($validated, ['key', 'name']),
            ], 'Role has been created.');

            return $role;
        });
    }

    public function update(Role $role, array $validated): Role
    {
        return DB::transaction(function () use ($role, $validated) {
            $trackedFields = ['key', 'name'];
            $before = $role->only($trackedFields);

            $updated = $this->roleRepository->updateRole($role, $validated);

            $this->activityLogService->logModelUpdated(
                $updated,
                $before,
                $trackedFields,
                ['attributes' => Arr::only($validated, ['key', 'name'])],
                'Role has been updated.'
            );

            return $updated;
        });
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $attributes = $role->only(['id', 'key', 'name']);
            $this->roleRepository->deleteRole($role);

            $this->activityLogService->log('deleted', $role, [
                'attributes' => $attributes,
            ], 'Role has been deleted.');
        });
    }
}
