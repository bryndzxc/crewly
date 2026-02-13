<?php

namespace App\DTO;

class EmployeeIncidentUpdateData
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $action_taken,
        public readonly ?string $follow_up_date,
        public readonly ?int $assigned_to,
    ) {}

    public static function fromArray(array $data): self
    {
        $assigned = $data['assigned_to'] ?? null;

        return new self(
            status: (string) ($data['status'] ?? 'OPEN'),
            action_taken: $data['action_taken'] ?? null,
            follow_up_date: $data['follow_up_date'] ?? null,
            assigned_to: $assigned === null || $assigned === '' ? null : (int) $assigned,
        );
    }
}
