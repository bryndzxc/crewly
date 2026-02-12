<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LeaveRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'employee_id')],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable', 'boolean'],
            'half_day_part' => ['nullable', Rule::in(['AM', 'PM'])],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employeeId = (int) $this->input('employee_id');
            $leaveTypeId = (int) $this->input('leave_type_id');
            $startDate = (string) $this->input('start_date');
            $endDate = (string) $this->input('end_date');
            $isHalfDay = (bool) $this->boolean('is_half_day');
            $halfDayPart = $this->input('half_day_part');

            /** @var LeaveType|null $type */
            $type = LeaveType::query()->find($leaveTypeId);
            if (!$type) {
                return;
            }

            if ($isHalfDay) {
                if (!$type->allow_half_day) {
                    $validator->errors()->add('is_half_day', 'Half-day is not allowed for this leave type.');
                }

                if ($startDate !== $endDate) {
                    $validator->errors()->add('start_date', 'Half-day leave must have the same start and end date.');
                }

                if (!$halfDayPart) {
                    $validator->errors()->add('half_day_part', 'Please select AM or PM for half-day leave.');
                }
            }

            if (!$isHalfDay && $halfDayPart) {
                $validator->errors()->add('half_day_part', 'Half-day part can only be set when half-day is enabled.');
            }

            if (!Employee::query()->where('employee_id', $employeeId)->exists()) {
                return;
            }

            if ($startDate !== '' && $endDate !== '') {
                $hasOverlap = LeaveRequest::query()
                    ->where('status', LeaveRequest::STATUS_APPROVED)
                    ->overlaps($employeeId, $startDate, $endDate)
                    ->exists();

                if ($hasOverlap) {
                    $validator->errors()->add('start_date', 'This leave overlaps with an existing approved leave.');
                }
            }
        });
    }
}
