<?php

namespace App\Services;

use App\Models\Department;
use App\Repositories\DepartmentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentService extends Service
{
    private DepartmentRepository $departmentRepository;

    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
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
            return $this->departmentRepository->createDepartment($validated);
        });
    }

    public function update(Department $department, array $validated): Department
    {
        return DB::transaction(function () use ($department, $validated) {
            return $this->departmentRepository->updateDepartment($department, $validated);
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(function () use ($department) {
            $this->departmentRepository->deleteDepartment($department);
        });
    }
}
