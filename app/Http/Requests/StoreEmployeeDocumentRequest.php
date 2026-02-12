<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('employees-documents-upload');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'Document Type',
            'files' => 'Files',
            'files.*' => 'File',
            'issue_date' => 'Issue Date',
            'expiry_date' => 'Expiry Date',
            'notes' => 'Notes',
        ];
    }
}
