<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:50'],
            'company_size' => ['nullable', 'string', 'in:1-10,11-50,51-200,200+'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source_page' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_size.in' => 'Please choose a valid company size.',
        ];
    }
}
