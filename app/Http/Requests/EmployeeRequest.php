<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->exists('date_hired')) {
            $this->merge([
                'date_hired' => $this->normalizeDateInput($this->input('date_hired')),
            ]);
        }

        if ($this->exists('regularization_date')) {
            $this->merge([
                'regularization_date' => $this->normalizeDateInput($this->input('regularization_date')),
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email_hash' => $this->hashEmail((string) $this->input('email')),
            ]);
        }

        if ($this->has('mobile_number')) {
            $this->merge([
                'mobile_number_hash' => $this->hashMobileNumber($this->input('mobile_number')),
            ]);
        }

        if ($this->has('first_name')) {
            $this->merge($this->buildNameIndexes('first_name', (string) $this->input('first_name')));
        }

        if ($this->has('last_name')) {
            $this->merge($this->buildNameIndexes('last_name', (string) $this->input('last_name')));
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreate = $this->routeIs('employees.store');
        $employeeId = $this->resolveEmployeeId();

        $required = $isCreate ? ['required'] : ['sometimes', 'required'];
        $optional = $isCreate ? ['nullable'] : ['sometimes'];

        $rules = [
            'department_id' => [
                ...$required,
                'integer',
                Rule::exists('departments', 'department_id')->whereNull('deleted_at'),
            ],
            'employee_code' => [
                ...$required,
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9_\-]+$/',
                Rule::unique('employees', 'employee_code')
                    ->whereNull('deleted_at')
                    ->ignore($employeeId, 'employee_id'),
            ],
            'first_name' => [
                ...$required,
                'string',
                'max:255',
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'last_name' => [
                ...$required,
                'string',
                'max:255',
            ],
            'suffix' => [
                'nullable',
                'string',
                'max:50',
            ],
            'email' => [
                ...$required,
                'string',
                'email',
                'max:255',
            ],
            'email_hash' => [
                ...$required,
                'string',
                'size:64',
                Rule::unique('employees', 'email_hash')
                    ->whereNull('deleted_at')
                    ->ignore($employeeId, 'employee_id'),
            ],
            'mobile_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'mobile_number_hash' => [
                $isCreate ? 'nullable' : 'sometimes',
                'nullable',
                'string',
                'size:64',
                Rule::unique('employees', 'mobile_number_hash')
                    ->whereNull('deleted_at')
                    ->ignore($employeeId, 'employee_id'),
            ],
            'first_name_bi' => [$isCreate ? 'nullable' : 'sometimes', 'nullable', 'string', 'size:64'],
            'last_name_bi' => [$isCreate ? 'nullable' : 'sometimes', 'nullable', 'string', 'size:64'],
            'first_name_prefix_bi' => [$isCreate ? 'nullable' : 'sometimes', 'nullable', 'array'],
            'last_name_prefix_bi' => [$isCreate ? 'nullable' : 'sometimes', 'nullable', 'array'],
            'status' => [
                ...$optional,
                Rule::in(['Active', 'Inactive', 'On Leave', 'Terminated', 'Resigned']),
            ],
            'position_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'date_hired' => [
                ...$optional,
                'nullable',
                'date',
            ],
            'regularization_date' => [
                ...$optional,
                'nullable',
                'date',
            ],
            'employment_type' => [
                ...$optional,
                Rule::in(['Full-Time', 'Part-Time', 'Contractor', 'Intern']),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            // Allow larger uploads; image is optimized before encryption anyway.
            'photo' => [$isCreate ? 'nullable' : 'sometimes', 'nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png'],
            // Client-side hint: used to detect when a selected photo fails to reach the server
            // (usually due to PHP upload_max_filesize/post_max_size).
            'photo_present' => [$isCreate ? 'nullable' : 'sometimes', 'nullable', 'boolean'],
        ];

        if ($isCreate) {
            $rules = array_merge($rules, [
                'document_type' => ['nullable', 'string', 'max:50', 'required_with:document_files'],
                'document_files' => ['nullable', 'array', 'min:1', 'max:10', 'required_with:document_type'],
                'document_files.*' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],

                'document_items' => ['nullable', 'array', 'min:1', 'max:10'],
                'document_items.*.file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
                'document_items.*.type' => ['required', 'string', 'max:50'],
                'document_items.*.issue_date' => ['nullable', 'date'],
                'document_items.*.expiry_date' => ['nullable', 'date'],

                'document_issue_date' => ['nullable', 'date'],
                'document_expiry_date' => ['nullable', 'date', 'after_or_equal:document_issue_date'],
                'document_notes' => ['nullable', 'string', 'max:2000'],
            ]);
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'department_id' => 'Department',
            'employee_code' => 'Employee Code',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'suffix' => 'Suffix',
            'email' => 'Email Address',
            'email_hash' => 'Email Address',
            'mobile_number' => 'Mobile Number',
            'mobile_number_hash' => 'Mobile Number',
            'status' => 'Status',
            'position_title' => 'Position Title',
            'date_hired' => 'Date Hired',
            'regularization_date' => 'Regularization Date',
            'employment_type' => 'Employment Type',
            'notes' => 'Notes',
            'photo' => 'Photo',
            'document_type' => 'Document Type',
            'document_files' => 'Document Files',
            'document_files.*' => 'Document File',
            'document_issue_date' => 'Document Issue Date',
            'document_expiry_date' => 'Document Expiry Date',
            'document_notes' => 'Document Notes',
            'document_items' => 'Documents',
            'document_items.*.file' => 'Document File',
            'document_items.*.type' => 'Document Type',
            'document_items.*.issue_date' => 'Document Issue Date',
            'document_items.*.expiry_date' => 'Document Expiry Date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // If the user selected a photo but it didn't arrive as a valid upload,
            // surface a helpful error instead of silently ignoring it.
            $photoPresent = (bool) $this->boolean('photo_present');
            if ($photoPresent) {
                $photo = $this->file('photo');
                if (!$photo || (method_exists($photo, 'isValid') && !$photo->isValid())) {
                    $v->errors()->add('photo', 'Photo failed to upload. Try a smaller file or increase PHP upload_max_filesize and post_max_size.');
                }
            }

            $items = $this->input('document_items');
            if (!is_array($items)) {
                return;
            }

            foreach ($items as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $issue = $item['issue_date'] ?? null;
                $expiry = $item['expiry_date'] ?? null;
                if (!$issue || !$expiry) {
                    continue;
                }

                try {
                    $issueDt = new \DateTimeImmutable((string) $issue);
                    $expiryDt = new \DateTimeImmutable((string) $expiry);
                } catch (\Throwable) {
                    continue;
                }

                if ($expiryDt < $issueDt) {
                    $v->errors()->add("document_items.$idx.expiry_date", 'The expiry date must be after or equal to the issue date.');
                }
            }
        });
    }

    private function hashEmail(string $email): string
    {
        $normalized = strtolower(trim($email));
        $key = (string) config('app.key', '');

        return hash_hmac('sha256', $normalized, $key);
    }

    private function hashMobileNumber(mixed $mobileNumber): ?string
    {
        if ($mobileNumber === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', trim((string) $mobileNumber));
        if ($normalized === '') {
            return null;
        }

        $key = (string) config('app.key', '');

        return hash_hmac('sha256', $normalized, $key);
    }

    private function resolveEmployeeId(): ?int
    {
        $routeEmployee = $this->route('employee');

        if ($routeEmployee instanceof Employee) {
            return (int) $routeEmployee->employee_id;
        }

        if (is_numeric($routeEmployee)) {
            return (int) $routeEmployee;
        }

        if (is_numeric($this->input('employee_id'))) {
            return (int) $this->input('employee_id');
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNameIndexes(string $field, string $value): array
    {
        $normalized = $this->normalizeName($value);
        if ($normalized === '') {
            return [
                "{$field}_bi" => null,
                "{$field}_prefix_bi" => null,
            ];
        }

        $key = (string) config('app.key', '');
        $bi = hash_hmac('sha256', $normalized, $key);

        $parts = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $prefixes = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $maxLen = min(10, mb_strlen($part));
            for ($i = 1; $i <= $maxLen; $i++) {
                $prefix = mb_substr($part, 0, $i);
                $prefixes[] = hash_hmac('sha256', $prefix, $key);
            }
        }

        $prefixes = array_values(array_unique($prefixes));

        return [
            "{$field}_bi" => $bi,
            "{$field}_prefix_bi" => $prefixes,
        ];
    }

    private function normalizeName(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = preg_replace('/\s+/', ' ', $v) ?? '';
        // Keep common name characters; drop other punctuation.
        $v = preg_replace('/[^\p{L}\p{N}\s\-\']+/u', '', $v) ?? '';
        $v = trim($v);
        return $v;
    }

    private function normalizeDateInput(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $trimmed, $m)) {
            return $m[1];
        }

        return $trimmed;
    }
}
