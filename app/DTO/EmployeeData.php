<?php

namespace App\DTO;

class EmployeeData
{
    public function __construct(
        public readonly ?int $employee_id,
        public readonly int $department_id,
        public readonly string $employee_code,
        public readonly string $first_name,
        public readonly ?string $middle_name,
        public readonly string $last_name,
        public readonly ?string $suffix,
        public readonly string $email,
        public readonly ?string $mobile_number,
        public readonly string $status,
        public readonly ?string $position_title,
        public readonly ?string $date_hired,
        public readonly ?string $regularization_date,
        public readonly string $employment_type,
        public readonly ?string $notes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employee_id: $data['employee_id'] ?? null,
            department_id: $data['department_id'],
            employee_code: $data['employee_code'],
            first_name: $data['first_name'],
            middle_name: $data['middle_name'] ?? null,
            last_name: $data['last_name'],
            suffix: $data['suffix'] ?? null,
            email: $data['email'],
            mobile_number: $data['mobile_number'] ?? null,
            status: $data['status'] ?? 'Active',
            position_title: $data['position_title'] ?? null,
            date_hired: $data['date_hired'] ?? null,
            regularization_date: $data['regularization_date'] ?? null,
            employment_type: $data['employment_type'] ?? 'Full-Time',
            notes: $data['notes'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'department_id' => $this->department_id,
            'employee_code' => $this->employee_code,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'status' => $this->status,
            'position_title' => $this->position_title,
            'date_hired' => $this->date_hired,
            'regularization_date' => $this->regularization_date,
            'employment_type' => $this->employment_type,
            'notes' => $this->notes,
        ];
    }
}