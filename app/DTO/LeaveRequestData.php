<?php

namespace App\DTO;

class LeaveRequestData
{
    public function __construct(
        public readonly int $employee_id,
        public readonly int $leave_type_id,
        public readonly string $start_date,
        public readonly string $end_date,
        public readonly bool $is_half_day,
        public readonly ?string $half_day_part,
        public readonly ?string $reason,
    ) {}

    public static function fromArray(array $data): self
    {
        $isHalfDay = array_key_exists('is_half_day', $data) ? (bool) $data['is_half_day'] : false;

        return new self(
            employee_id: (int) $data['employee_id'],
            leave_type_id: (int) $data['leave_type_id'],
            start_date: (string) $data['start_date'],
            end_date: (string) $data['end_date'],
            is_half_day: $isHalfDay,
            half_day_part: $isHalfDay ? ($data['half_day_part'] ?? null) : null,
            reason: $data['reason'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_half_day' => $this->is_half_day,
            'half_day_part' => $this->half_day_part,
            'reason' => $this->reason,
        ];
    }
}
