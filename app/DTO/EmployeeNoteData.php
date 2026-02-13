<?php

namespace App\DTO;

use App\Models\EmployeeNote;

class EmployeeNoteData
{
    public function __construct(
        public readonly string $note_type,
        public readonly string $note,
        public readonly ?string $follow_up_date,
    ) {}

    public static function fromArray(array $data): self
    {
        $noteType = (string) ($data['note_type'] ?? EmployeeNote::TYPE_GENERAL);
        $noteType = trim($noteType) === '' ? EmployeeNote::TYPE_GENERAL : $noteType;

        return new self(
            note_type: $noteType,
            note: (string) ($data['note'] ?? ''),
            follow_up_date: $data['follow_up_date'] ?? null,
        );
    }
}
