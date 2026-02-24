<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemoTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role() !== 'employee';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', Rule::unique('memo_templates', 'slug')->whereNull('company_id')],
            'description' => ['nullable', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
