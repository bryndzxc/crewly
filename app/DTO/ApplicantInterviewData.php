<?php

namespace App\DTO;

class ApplicantInterviewData
{
    public function __construct(
        public readonly ?string $scheduled_at,
        public readonly ?string $notes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            scheduled_at: $data['scheduled_at'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'scheduled_at' => $this->scheduled_at,
            'notes' => $this->notes,
        ];
    }
}
