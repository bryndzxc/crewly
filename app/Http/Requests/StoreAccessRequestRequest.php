<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'company_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:50'],
            'employee_count_range' => ['required', 'string', 'in:1-20,21-50,51-100,101-200'],
            'requested_plan' => ['required', 'string', Rule::in([Company::PLAN_STARTER, Company::PLAN_GROWTH, Company::PLAN_PRO])],
            'industry' => ['nullable', 'string', 'max:120'],
            'current_process' => ['nullable', 'string', 'max:2000'],
            'biggest_pain' => ['nullable', 'string', 'max:2000'],
            'agree_to_terms' => ['accepted'],
            'source_page' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_count_range.in' => 'Please choose a valid employee count range.',
            'agree_to_terms.accepted' => 'Please agree to the Terms and Privacy Policy to continue.',
        ];
    }
}
