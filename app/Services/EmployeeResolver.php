<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeResolver
{
    public function current(?User $user = null): ?Employee
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        // Prefer relationship if loaded; otherwise fetch via user_id.
        if ($user->relationLoaded('employee')) {
            return $user->getRelation('employee');
        }

        return Employee::query()
            ->where('user_id', (int) $user->id)
            ->first();
    }

    public function requireCurrent(?User $user = null): Employee
    {
        $employee = $this->current($user);
        if (!$employee) {
            abort(403, 'No employee record is linked to this account.');
        }

        return $employee;
    }

    public function requireCurrentId(?User $user = null): int
    {
        return (int) $this->requireCurrent($user)->employee_id;
    }
}
