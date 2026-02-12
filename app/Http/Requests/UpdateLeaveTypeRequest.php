<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LeaveType|null $leaveType */
        $leaveType = $this->route('type');

        return $leaveType ? ($this->user()?->can('update', $leaveType) ?? false) : false;
    }

    public function rules(): array
    {
        /** @var LeaveType|null $leaveType */
        $leaveType = $this->route('type');
        $id = $leaveType?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_\-]+$/', Rule::unique('leave_types', 'code')->ignore($id)],
            'requires_approval' => ['nullable', 'boolean'],
            'paid' => ['nullable', 'boolean'],
            'allow_half_day' => ['nullable', 'boolean'],
            'default_annual_credits' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);
    }
}
