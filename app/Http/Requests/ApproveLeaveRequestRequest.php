<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApproveLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LeaveRequest|null $leaveRequest */
        $leaveRequest = $this->route('leaveRequest');

        return $leaveRequest ? ($this->user()?->can('approve', $leaveRequest) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var LeaveRequest|null $leaveRequest */
            $leaveRequest = $this->route('leaveRequest');
            if (!$leaveRequest) {
                return;
            }

            if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
                $validator->errors()->add('status', 'Only pending requests can be approved.');
                return;
            }

            $startDate = $leaveRequest->start_date?->format('Y-m-d');
            $endDate = $leaveRequest->end_date?->format('Y-m-d');

            if ($startDate && $endDate) {
                $hasOverlap = LeaveRequest::query()
                    ->where('status', LeaveRequest::STATUS_APPROVED)
                    ->where('employee_id', (int) $leaveRequest->employee_id)
                    ->where('id', '!=', (int) $leaveRequest->id)
                    ->whereDate('start_date', '<=', $endDate)
                    ->whereDate('end_date', '>=', $startDate)
                    ->exists();

                if ($hasOverlap) {
                    $validator->errors()->add('start_date', 'This leave overlaps with an existing approved leave.');
                }
            }
        });
    }
}
