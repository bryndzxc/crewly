<?php

namespace App\DTO;

class LeaveDecisionData
{
    public function __construct(
        public readonly ?string $decision_notes,
    ) {}

    public static function fromArray(array $data): self
    {
        $notes = $data['decision_notes'] ?? null;
        $notes = is_string($notes) ? trim($notes) : null;

        return new self(
            decision_notes: $notes !== '' ? $notes : null,
        );
    }

    public function toArray(): array
    {
        return [
            'decision_notes' => $this->decision_notes,
        ];
    }
}
