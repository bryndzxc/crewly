<?php

namespace App\Repositories;

use App\Models\Department;

class DepartmentRepository extends BaseRepository
{
    public function createDepartment(array $attributes): Department
    {
        return Department::create($attributes);
    }

    public function updateDepartment(Department $department, array $attributes): Department
    {
        $department->update($attributes);

        return $department;
    }

    public function deleteDepartment(Department $department): void
    {
        $department->delete();
    }
}
