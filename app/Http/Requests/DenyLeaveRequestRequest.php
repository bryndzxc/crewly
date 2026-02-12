<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DenyLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LeaveRequest|null $leaveRequest */
        $leaveRequest = $this->route('leaveRequest');

        return $leaveRequest ? ($this->user()?->can('deny', $leaveRequest) ?? false) : false;
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
                $validator->errors()->add('status', 'Only pending requests can be denied.');
            }
        });
    }
}
