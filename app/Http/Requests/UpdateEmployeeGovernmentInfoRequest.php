<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateEmployeeGovernmentInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::check('access-employees');
    }

    protected function prepareForValidation(): void
    {
        $fields = ['sss_number', 'philhealth_number', 'pagibig_number', 'tin_number'];

        $data = [];
        foreach ($fields as $f) {
            $v = $this->input($f);
            if ($v === null) {
                $data[$f] = null;
                continue;
            }

            $v = trim((string) $v);
            $data[$f] = $v === '' ? null : $v;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $pattern = '/^[0-9A-Za-z\-\s\.]+$/';

        return [
            'sss_number' => ['nullable', 'string', 'max:50', 'regex:' . $pattern],
            'philhealth_number' => ['nullable', 'string', 'max:50', 'regex:' . $pattern],
            'pagibig_number' => ['nullable', 'string', 'max:50', 'regex:' . $pattern],
            'tin_number' => ['nullable', 'string', 'max:50', 'regex:' . $pattern],
        ];
    }

    public function attributes(): array
    {
        return [
            'sss_number' => 'SSS Number',
            'philhealth_number' => 'PhilHealth Number',
            'pagibig_number' => 'Pag-IBIG Number',
            'tin_number' => 'TIN',
        ];
    }
}
