<?php

namespace App\Http\Requests;

use App\Models\EmployeeIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateEmployeeIncidentRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(EmployeeIncident::statuses())],
            'action_taken' => ['nullable', 'string', 'max:10000'],
            'follow_up_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'Status',
            'action_taken' => 'Action Taken',
            'follow_up_date' => 'Follow-up Date',
            'assigned_to' => 'Assigned To',
        ];
    }
}
