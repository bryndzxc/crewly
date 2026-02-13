<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreApplicantInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('recruitment-interviews-create');
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
