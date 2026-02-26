<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;

class UpsertAttendanceBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('manage-attendance');
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', 'integer'],
            'rows.*.status' => ['nullable', 'in:' . implode(',', [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_ABSENT])],
            'rows.*.time_in' => ['nullable', 'date_format:H:i'],
            'rows.*.time_out' => ['nullable', 'date_format:H:i'],
            'rows.*.remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rows = $this->input('rows');
            if (!is_array($rows)) {
                return;
            }

            foreach ($rows as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $status = (string) ($row['status'] ?? '');
                $timeIn = (string) ($row['time_in'] ?? '');
                $timeOut = (string) ($row['time_out'] ?? '');

                if ($status === AttendanceRecord::STATUS_PRESENT) {
                    if (($timeIn !== '' && $timeOut === '') || ($timeIn === '' && $timeOut !== '')) {
                        $validator->errors()->add("rows.$i.time_out", 'Time in and time out must both be provided for PRESENT.');
                        continue;
                    }

                    if ($timeIn !== '' && $timeOut !== '' && $timeOut < $timeIn) {
                        $validator->errors()->add("rows.$i.time_out", 'Time out must be after time in.');
                    }
                }
            }
        });
    }
}
