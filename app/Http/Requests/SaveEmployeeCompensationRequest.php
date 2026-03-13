<?php

namespace App\Http\Requests;

use App\Models\EmployeeCompensation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveEmployeeCompensationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('access-employees');
    }

    public function rules(): array
    {
        return [
            'salary_type' => ['required', 'string', Rule::in(EmployeeCompensation::salaryTypes())],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'pay_frequency' => ['required', 'string', Rule::in(EmployeeCompensation::payFrequencies())],
            'effective_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'change_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'salary_type' => 'Salary Type',
            'base_salary' => 'Base Salary',
            'pay_frequency' => 'Pay Frequency',
            'effective_date' => 'Effective Date',
            'notes' => 'Notes',
            'change_reason' => 'Change Reason',
        ];
    }
}