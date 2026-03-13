<?php

namespace App\Http\Requests;

use App\Models\EmployeeAllowance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveEmployeeAllowanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('access-employees');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'taxable' => $this->boolean('taxable'),
        ]);
    }

    public function rules(): array
    {
        return [
            'allowance_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'string', Rule::in(EmployeeAllowance::frequencies())],
            'taxable' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'allowance_name' => 'Allowance Name',
            'amount' => 'Amount',
            'frequency' => 'Frequency',
            'taxable' => 'Taxable',
        ];
    }
}