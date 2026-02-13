<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreApplicantDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('recruitment-documents-upload');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
