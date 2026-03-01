<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;
use App\DTO\EmployeeData;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\ActivityLogService;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeeService extends Service
{

    private ActivityLogService $activityLogService;
    private EmployeeDocumentService $employeeDocumentService;
    private EmployeePhotoService $employeePhotoService;
    
    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
        private readonly EmployeePortalUserService $employeePortalUserService,
        ActivityLogService $activityLogService,
        EmployeeDocumentService $employeeDocumentService,
        EmployeePhotoService $employeePhotoService
    )
    {
        $this->activityLogService = $activityLogService;
        $this->employeeDocumentService = $employeeDocumentService;
        $this->employeePhotoService = $employeePhotoService;
    }

    public function index(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return [
            'employees' => Employee::selectedFields()
                ->searchable($q)
                ->sortable()
                ->pagination($perPage),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Create a new employee.
     */
    public function create(array $validated): Employee
    {
        $employeeData = EmployeeData::fromArray($validated);

        $employee = DB::transaction(function () use ($employeeData) {
            // return $this->employeeRepository->createEmployee($employeeData);
            return $this->processEmployeeCreation($employeeData);
        });

        $portalPasswordPlain = null;
        $portalCreatedNew = false;

        try {
            $linked = $this->employeePortalUserService->ensureLinkedWithPassword($employee);
            $portalCreatedNew = (bool) ($linked['created_new'] ?? false);
            $portalPasswordPlain = is_string($linked['password_plain'] ?? null) ? (string) $linked['password_plain'] : null;
        } catch (\Throwable $e) {
            Log::warning('Employee portal user auto-link failed.', [
                'employee_id' => (int) $employee->employee_id,
                'email' => (string) ($employee->email ?? ''),
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Employee created, but the portal account could not be created automatically.');
        }

        $this->handleInitialDocumentsIfAny($employee, $validated);
        $this->handlePhotoIfAny($employee, $validated);

        if ($portalCreatedNew && is_string($portalPasswordPlain) && trim($portalPasswordPlain) !== '') {
            $reminder = "Portal account created. Default password: {$portalPasswordPlain}. The user must change it after logging in.";
            $existing = (string) session()->get('success', 'Employee created successfully.');
            session()->flash('success', trim($existing . ' ' . $reminder));
        }

        return $employee;
    }

    private function handlePhotoIfAny(Employee $employee, array $validated): void
    {
        $photo = $validated['photo'] ?? null;
        if (!$photo instanceof UploadedFile) {
            return;
        }

        try {
            $this->employeePhotoService->setPhoto($employee, $photo);
        } catch (\Throwable $e) {
        
            session()->flash('error', 'Employee created, but the photo upload failed.');
        }
    }

    private function handleInitialDocumentsIfAny(Employee $employee, array $validated): void
    {
        $items = $validated['document_items'] ?? null;
        if (is_array($items) && count($items) > 0) {
            Gate::authorize('employees-documents-upload');

            $shared = [
                'notes' => $validated['document_notes'] ?? null,
            ];

            try {
                $this->employeeDocumentService->uploadItems($employee, $items, $shared, Auth::id());
                session()->flash('success', 'Employee created successfully (documents uploaded).');
            } catch (\Throwable $e) {
                session()->flash('success', 'Employee created successfully.');
                session()->flash('error', 'Employee created, but the initial document upload failed.');
            }

            return;
        }

        $files = $validated['document_files'] ?? null;
        if (!is_array($files) || count($files) === 0) {
            session()->flash('success', 'Employee created successfully.');
            return;
        }

        // Authorization
        Gate::authorize('employees-documents-upload');

        $meta = [
            'type' => (string) ($validated['document_type'] ?? 'Document'),
            'issue_date' => $validated['document_issue_date'] ?? null,
            'expiry_date' => $validated['document_expiry_date'] ?? null,
            'notes' => $validated['document_notes'] ?? null,
        ];

        try {
            $this->employeeDocumentService->uploadMany($employee, $meta, $files, Auth::id());
            session()->flash('success', 'Employee created successfully (documents uploaded).');
        } catch (\Throwable $e) {
            // Employee already created; surface upload failure without blocking.
            session()->flash('success', 'Employee created successfully.');
            session()->flash('error', 'Employee created, but the initial document upload failed.');
        }
    }

    /**
     * Update an existing employee.
     */
    public function update(Employee $employee, array $data): Employee
    {
        $payload = $this->mergeEmployeePayload($employee, $data);
        $employeeData = EmployeeData::fromArray($payload);

        $updated = DB::transaction(function () use ($employee, $employeeData) {
            return $this->processEmployeeUpdate($employee, $employeeData);
        });

        $this->handlePhotoOnUpdateIfAny($updated, $data);

        return $updated;
    }

    private function handlePhotoOnUpdateIfAny(Employee $employee, array $validated): void
    {
        $photo = $validated['photo'] ?? null;
        if (!$photo instanceof UploadedFile) {
            return;
        }

        try {
            $this->employeePhotoService->setPhoto($employee, $photo);
            session()->flash('success', 'Employee updated successfully (photo updated).');
        } catch (\Throwable $e) {

            session()->flash('error', 'Employee updated, but the photo upload failed.');
        }
    }

    public function delete(Employee $employee): void
    {
        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
        $documentPaths = [];
        $photoPath = null;

        DB::transaction(function () use ($employee, &$documentPaths, &$photoPath) {
            $attributes = $employee->getAttributes();

            $documentPaths = EmployeeDocument::query()
                ->where('employee_id', (int) $employee->employee_id)
                ->pluck('file_path')
                ->filter()
                ->values()
                ->all();

            $photoPath = (string) ($employee->getAttribute('photo_path') ?? '');

            app(\App\Services\AuditLogger::class)->log(
                'employee.deleted',
                $employee,
                [
                    'employee_id' => (int) $employee->employee_id,
                    'employee_code' => (string) $employee->employee_code,
                    'department_id' => (int) $employee->department_id,
                    'status' => (string) $employee->status,
                ],
                [],
                [
                    'documents_count' => count($documentPaths),
                    'had_photo' => $photoPath !== '',
                ],
                'Employee deleted.'
            );

            // Remove DB references first (employee is soft-deleted, so FK cascade does not run).
            EmployeeDocument::query()
                ->where('employee_id', (int) $employee->employee_id)
                ->delete();

            // Clear photo metadata so nothing references storage after delete.
            $employee->forceFill([
                'photo_path' => null,
                'photo_original_name' => null,
                'photo_mime_type' => null,
                'photo_size' => null,
                'photo_is_encrypted' => false,
                'photo_encryption_algo' => null,
                'photo_encryption_iv' => null,
                'photo_encryption_tag' => null,
                'photo_key_version' => null,
            ])->save();

            $this->employeeRepository->deleteEmployee($employee);

            $this->activityLogService->log('deleted', $employee, [
                'attributes' => $attributes,
            ], 'Employee has been deleted.');
        });

        // Best-effort cleanup of encrypted blobs after DB commit.
        try {
            if (is_string($photoPath) && $photoPath !== '') {
                Storage::disk($disk)->delete($photoPath);
            }

            foreach ($documentPaths as $path) {
                if (!is_string($path) || $path === '') {
                    continue;
                }
                Storage::disk($disk)->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Employee asset cleanup failed after delete.', [
                'employee_id' => (int) $employee->employee_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function processEmployeeCreation(EmployeeData $employeeData): Employee
    {
        $employee = $this->employeeRepository->createEmployee($employeeData->toArray());

        // Do not store plaintext PII in activity logs.
        $this->activityLogService->log('created', $employee, [
            'attributes' => [
                'employee_id' => $employee->employee_id,
                'employee_code' => $employee->employee_code,
                'department_id' => $employee->department_id,
                'status' => $employee->status,
            ],
        ], 'Employee has been created.');

        app(\App\Services\AuditLogger::class)->log(
            'employee.created',
            $employee,
            [],
            [
                'employee_id' => (int) $employee->employee_id,
                'employee_code' => (string) $employee->employee_code,
                'department_id' => (int) $employee->department_id,
                'status' => (string) $employee->status,
            ],
            [],
            'Employee created.'
        );

        return $employee;
    }

    private function processEmployeeUpdate(Employee $employee, EmployeeData $employeeData): Employee
    {
        // Limit activity log changes to non-PII fields.
        $trackedFields = [
            'department_id',
            'employee_code',
            'status',
            'date_hired',
            'regularization_date',
            'employment_type',
        ];
        $before = $employee->only($trackedFields);

        $updated = $this->employeeRepository->updateEmployee($employee, $employeeData->toArray());

        $after = $updated->only($trackedFields);
        app(\App\Services\AuditLogger::class)->log(
            'employee.updated',
            $updated,
            $before,
            $after,
            [],
            'Employee updated.'
        );

        $this->activityLogService->logModelUpdated(
            $updated,
            $before,
            $trackedFields,
            [
                'attributes' => [
                    'employee_id' => $employee->employee_id,
                    'employee_code' => $employee->employee_code,
                ],
            ],
            'Employee has been updated.'
        );

        return $updated;
    }

    private function mergeEmployeePayload(Employee $employee, array $data): array
    {
        $base = $employee->only([
            'employee_id',
            'department_id',
            'employee_code',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'email',
            'mobile_number',
            'status',
            'position_title',
            'date_hired',
            'regularization_date',
            'employment_type',
            'notes',
        ]);

        $base['date_hired'] = $employee->date_hired?->format('Y-m-d');
        $base['regularization_date'] = $employee->regularization_date?->format('Y-m-d');

        return array_merge($base, $data);
    }
}