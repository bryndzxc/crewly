<?php

namespace App\DTO;

class ApplicantHireData
{
    public function __construct(
        public readonly int $department_id,
        public readonly string $email,
        public readonly ?string $mobile_number,
        public readonly ?string $position_title,
        public readonly ?string $date_hired,
        public readonly bool $migrate_resume,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            department_id: (int) $data['department_id'],
            email: (string) $data['email'],
            mobile_number: $data['mobile_number'] ?? null,
            position_title: $data['position_title'] ?? null,
            date_hired: $data['date_hired'] ?? null,
            migrate_resume: (bool) ($data['migrate_resume'] ?? false),
        );
    }
}
