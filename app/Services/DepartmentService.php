<?php

namespace App\Services;

use App\Models\Department;
use App\Repositories\DepartmentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentService extends Service
{
    private DepartmentRepository $departmentRepository;
    private ActivityLogService $activityLogService;

    public function __construct(DepartmentRepository $departmentRepository, ActivityLogService $activityLogService)
    {
        $this->departmentRepository = $departmentRepository;
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
            'departments' => Department::selectedFields()
                ->searchable($q)
                ->sortable()
                ->pagination($perPage, ['department_id', 'name', 'code']),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ];
    }

    public function create(array $validated): Department
    {
        return DB::transaction(function () use ($validated) {
            $department = $this->departmentRepository->createDepartment($validated);

            $this->activityLogService->log('created', $department, [
                'attributes' => $validated,
            ], 'Department has been created.');

            return $department;
        });
    }

    public function update(Department $department, array $validated): Department
    {
        return DB::transaction(function () use ($department, $validated) {
            $trackedFields = ['name', 'code'];
            $before = $department->only($trackedFields);

            $updated = $this->departmentRepository->updateDepartment($department, $validated);

            $this->activityLogService->logModelUpdated(
                $updated,
                $before,
                $trackedFields,
                ['attributes' => $validated],
                'Department has been updated.'
            );

            return $updated;
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(function () use ($department) {
            $attributes = $department->getAttributes();

            $this->departmentRepository->deleteDepartment($department);

            $this->activityLogService->log('deleted', $department, [
                'attributes' => $attributes,
            ], 'Department has been deleted.');
        });
    }
}
