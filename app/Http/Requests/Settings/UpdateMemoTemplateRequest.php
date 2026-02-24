<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemoTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role() !== 'employee';
    }

    public function rules(): array
    {
        $template = $this->route('template');
        $templateId = is_object($template) ? ($template->id ?? null) : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:150',
                'alpha_dash',
                Rule::unique('memo_templates', 'slug')
                    ->ignore($templateId)
                    ->whereNull('company_id'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
