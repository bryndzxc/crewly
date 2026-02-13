<?php

namespace App\DTO;

class AttendanceRecordUpsertData
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $date,
        public readonly ?string $status,
        public readonly ?string $timeIn,
        public readonly ?string $timeOut,
        public readonly ?string $remarks,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(int $employeeId, array $data): self
    {
        $status = array_key_exists('status', $data) ? ($data['status'] !== null ? (string) $data['status'] : null) : null;
        $status = $status !== null ? trim($status) : null;
        if ($status === '') {
            $status = null;
        }

        $timeIn = array_key_exists('time_in', $data) ? ($data['time_in'] !== null ? (string) $data['time_in'] : null) : null;
        $timeIn = $timeIn !== null ? trim($timeIn) : null;
        if ($timeIn === '') {
            $timeIn = null;
        }

        $timeOut = array_key_exists('time_out', $data) ? ($data['time_out'] !== null ? (string) $data['time_out'] : null) : null;
        $timeOut = $timeOut !== null ? trim($timeOut) : null;
        if ($timeOut === '') {
            $timeOut = null;
        }

        $remarks = array_key_exists('remarks', $data) ? ($data['remarks'] !== null ? (string) $data['remarks'] : null) : null;
        $remarks = $remarks !== null ? trim($remarks) : null;
        if ($remarks === '') {
            $remarks = null;
        }

        return new self(
            employeeId: $employeeId,
            date: (string) ($data['date'] ?? ''),
            status: $status,
            timeIn: $timeIn,
            timeOut: $timeOut,
            remarks: $remarks,
        );
    }
}
