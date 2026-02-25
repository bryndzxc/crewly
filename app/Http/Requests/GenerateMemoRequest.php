<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;
        if ($user->isEmployee()) return false;

        return true;
    }

    public function rules(): array
    {
        $companyId = (int) ($this->user()?->company_id ?? 0);

        return [
            'memo_template_id' => [
                'required',
                'integer',
                Rule::exists('memo_templates', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', 1),
            ],
            'incident_summary' => ['nullable', 'string', 'max:5000'],
            'memo_date' => ['nullable', 'date'],
            'hr_signatory_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
