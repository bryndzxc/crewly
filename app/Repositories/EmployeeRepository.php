<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

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
            'first_name_bi' => $data['first_name_bi'] ?? null,
            'last_name_bi' => $data['last_name_bi'] ?? null,
            'first_name_prefix_bi' => $data['first_name_prefix_bi'] ?? null,
            'last_name_prefix_bi' => $data['last_name_prefix_bi'] ?? null,
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
            'first_name_bi' => $data['first_name_bi'] ?? $employee->first_name_bi,
            'last_name_bi' => $data['last_name_bi'] ?? $employee->last_name_bi,
            'first_name_prefix_bi' => $data['first_name_prefix_bi'] ?? $employee->first_name_prefix_bi,
            'last_name_prefix_bi' => $data['last_name_prefix_bi'] ?? $employee->last_name_prefix_bi,
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
}
