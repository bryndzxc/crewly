<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LeaveType::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_\-]+$/', Rule::unique('leave_types', 'code')],
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
