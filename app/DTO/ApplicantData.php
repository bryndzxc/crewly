<?php

namespace App\DTO;

use App\Models\Applicant;

class ApplicantData
{
    public function __construct(
        public readonly ?int $position_id,
        public readonly string $first_name,
        public readonly ?string $middle_name,
        public readonly string $last_name,
        public readonly ?string $suffix,
        public readonly ?string $email,
        public readonly ?string $mobile_number,
        public readonly ?string $source,
        public readonly string $stage,
        public readonly mixed $expected_salary,
        public readonly ?string $notes,
        public readonly ?string $applied_at,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            position_id: isset($data['position_id']) ? (int) $data['position_id'] : null,
            first_name: (string) ($data['first_name'] ?? ''),
            middle_name: $data['middle_name'] ?? null,
            last_name: (string) ($data['last_name'] ?? ''),
            suffix: $data['suffix'] ?? null,
            email: $data['email'] ?? null,
            mobile_number: $data['mobile_number'] ?? null,
            source: $data['source'] ?? null,
            stage: (string) ($data['stage'] ?? Applicant::STAGE_APPLIED),
            expected_salary: $data['expected_salary'] ?? null,
            notes: $data['notes'] ?? null,
            applied_at: $data['applied_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'position_id' => $this->position_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'source' => $this->source,
            'stage' => $this->stage,
            'expected_salary' => $this->expected_salary,
            'notes' => $this->notes,
            'applied_at' => $this->applied_at,
        ];
    }
}
