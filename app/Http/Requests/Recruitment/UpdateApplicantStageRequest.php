<?php

namespace App\Http\Requests\Recruitment;

use App\Models\Applicant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateApplicantStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('recruitment-stage-update');
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', Rule::in(Applicant::stages())],
        ];
    }
}
