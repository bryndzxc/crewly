<?php

namespace App\Http\Requests;

use App\Models\EmployeeNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreEmployeeNoteRequest extends FormRequest
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
            'note_type' => ['nullable', 'string', Rule::in(EmployeeNote::noteTypes())],
            'note' => ['required', 'string', 'max:10000'],
            'follow_up_date' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function attributes(): array
    {
        return [
            'note_type' => 'Note Type',
            'note' => 'Note',
            'follow_up_date' => 'Follow-up Date',
            'attachments' => 'Attachments',
            'attachments.*' => 'Attachment',
        ];
    }
}
