<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;

class UpsertAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('manage-attendance');
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:' . implode(',', [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_ABSENT])],
            'time_in' => ['nullable', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $status = (string) ($this->input('status') ?? '');
            $timeIn = (string) ($this->input('time_in') ?? '');
            $timeOut = (string) ($this->input('time_out') ?? '');

            if ($status === AttendanceRecord::STATUS_PRESENT) {
                if (($timeIn !== '' && $timeOut === '') || ($timeIn === '' && $timeOut !== '')) {
                    $validator->errors()->add('time_out', 'Time in and time out must both be provided for PRESENT.');
                    return;
                }

                if ($timeIn !== '' && $timeOut !== '' && $timeOut < $timeIn) {
                    $validator->errors()->add('time_out', 'Time out must be after time in.');
                }
            }
        });
    }
}
