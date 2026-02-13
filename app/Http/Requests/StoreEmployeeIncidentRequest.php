<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEmployeeIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('employees-relations-manage');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:120'],
            'incident_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:10000'],
            'follow_up_date' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => 'Category',
            'incident_date' => 'Incident Date',
            'description' => 'Description',
            'follow_up_date' => 'Follow-up Date',
            'attachments' => 'Attachments',
            'attachments.*' => 'Attachment',
        ];
    }
}
