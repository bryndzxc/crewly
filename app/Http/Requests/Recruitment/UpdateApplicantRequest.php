<?php

namespace App\Http\Requests\Recruitment;

use App\Models\Applicant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('recruitment-manage');
    }

    public function rules(): array
    {
        return [
            'position_id' => ['nullable', 'integer', 'exists:recruitment_positions,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'source' => ['nullable', 'string', 'max:255'],
            'stage' => ['required', 'string', Rule::in(Applicant::stages())],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'applied_at' => ['nullable', 'date'],
        ];
    }
}
