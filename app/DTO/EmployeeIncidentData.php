<?php

namespace App\DTO;

class EmployeeIncidentData
{
    public function __construct(
        public readonly string $category,
        public readonly string $incident_date,
        public readonly string $description,
        public readonly ?string $follow_up_date,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            category: (string) ($data['category'] ?? ''),
            incident_date: (string) ($data['incident_date'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            follow_up_date: $data['follow_up_date'] ?? null,
        );
    }
}
