<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class EmployeeRepository extends BaseRepository
{
    public function createEmployee(array $data): Employee
    {
        $emailHash = $this->hashEmail((string) $data['email']);
        $mobileNumberHash = $this->hashMobileNumber($data['mobile_number'] ?? null);

        return Employee::create([
            'department_id' => $data['department_id'],
            'employee_code' => $data['employee_code'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'email' => $data['email'],
            'email_hash' => $emailHash,
            'mobile_number' => $data['mobile_number'] ?? null,
            'mobile_number_hash' => $mobileNumberHash,
            'status' => $data['status'] ?? 'Active',
            'position_title' => $data['position_title'] ?? null,
            'date_hired' => $data['date_hired'] ?? null,
            'regularization_date' => $data['regularization_date'] ?? null,
            'employment_type' => $data['employment_type'] ?? 'Full-Time',
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $emailHash = $this->hashEmail((string) $data['email']);
        $mobileNumberHash = $this->hashMobileNumber($data['mobile_number'] ?? null);

        $employee->update([
            'department_id' => $data['department_id'],
            'employee_code' => $data['employee_code'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'email' => $data['email'],
            'email_hash' => $emailHash,
            'mobile_number' => $data['mobile_number'] ?? null,
            'mobile_number_hash' => $mobileNumberHash,
            'status' => $data['status'] ?? $employee->status,
            'position_title' => $data['position_title'] ?? null,
            'date_hired' => $data['date_hired'] ?? null,
            'regularization_date' => $data['regularization_date'] ?? null,
            'employment_type' => $data['employment_type'] ?? $employee->employment_type,
            'notes' => $data['notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        return $employee;
    }

    private function hashEmail(string $email): string
    {
        $normalized = strtolower(trim($email));
        $key = (string) config('app.key', '');

        return hash_hmac('sha256', $normalized, $key);
    }

    private function hashMobileNumber(mixed $mobileNumber): ?string
    {
        if ($mobileNumber === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', trim((string) $mobileNumber));
        if ($normalized === '') {
            return null;
        }

        $key = (string) config('app.key', '');

        return hash_hmac('sha256', $normalized, $key);
    }

    public function deleteEmployee(Employee $employee): void
    {
        $employee->forceFill([
            'deleted_by' => Auth::id(),
        ])->save();

        $employee->delete();
    }

    public function paginateProbationEndingSoon(
        Carbon $today,
        Carbon $end,
        array $allowedStatuses,
        string $search,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Employee::query()
            ->from('employees')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.department_id')
            ->select([
                'employees.employee_id',
                'employees.employee_code',
                'employees.department_id',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.status',
                'employees.regularization_date',
                'departments.name as department_name',
            ])
            ->whereNotNull('employees.regularization_date')
            ->whereBetween('employees.regularization_date', [$today->toDateString(), $end->toDateString()])
            ->when(count($allowedStatuses) > 0, fn (Builder $q) => $q->whereIn('employees.status', $allowedStatuses))
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('departments.name', 'like', '%' . $search . '%')
                        ->orWhere(function (Builder $empQ) use ($search) {
                            $empQ->searchable($search);
                        });
                });
            })
            ->orderBy('employees.regularization_date', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }
}
