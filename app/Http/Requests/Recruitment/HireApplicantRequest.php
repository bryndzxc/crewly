<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class HireApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('recruitment-hire');
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,department_id'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'date_hired' => ['nullable', 'date'],
            'migrate_resume' => ['nullable', 'boolean'],
        ];
    }
}
