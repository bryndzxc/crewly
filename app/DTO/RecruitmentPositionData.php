<?php

namespace App\DTO;

class RecruitmentPositionData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $department,
        public readonly ?string $location,
        public readonly string $status,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) ($data['title'] ?? ''),
            department: $data['department'] ?? null,
            location: $data['location'] ?? null,
            status: (string) ($data['status'] ?? 'OPEN'),
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'department' => $this->department,
            'location' => $this->location,
            'status' => $this->status,
        ];
    }
}
