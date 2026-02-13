<?php

namespace App\Repositories;

use App\DTO\AttendanceRecordUpsertData;
use App\Models\AttendanceRecord;
use Illuminate\Support\Collection;

class AttendanceRepository extends BaseRepository
{
    /**
     * @param array<int, int> $employeeIds
     * @return Collection<int, AttendanceRecord>
     */
    public function recordsForDate(array $employeeIds, string $date): Collection
    {
        return AttendanceRecord::query()
            ->whereDate('date', $date)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');
    }

    public function upsert(AttendanceRecordUpsertData $data, ?int $userId): AttendanceRecord
    {
        $payload = [
            'status' => $data->status,
            'time_in' => $data->timeIn,
            'time_out' => $data->timeOut,
            'remarks' => $data->remarks,
            'updated_by' => $userId,
        ];

        return AttendanceRecord::query()->updateOrCreate(
            [
                'employee_id' => $data->employeeId,
                'date' => $data->date,
            ],
            array_merge(
                $payload,
                [
                    'created_by' => $userId,
                ],
            )
        );
    }
}
