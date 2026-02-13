<?php

namespace App\Services;

use App\DTO\ApplicantHireData;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicantHireService extends Service
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function hire(Applicant $applicant, array $validated, ?int $userId): Employee
    {
        $dto = ApplicantHireData::fromArray($validated);

        /** @var Employee $employee */
        $employee = DB::transaction(function () use ($dto, $applicant, $userId) {
            $employeeCode = $this->nextEmployeeCode();

            $payload = [
                'department_id' => $dto->department_id,
                'employee_code' => $employeeCode,
                'first_name' => (string) ($applicant->first_name ?? ''),
                'middle_name' => $applicant->middle_name,
                'last_name' => (string) ($applicant->last_name ?? ''),
                'suffix' => $applicant->suffix,
                'email' => $dto->email,
                'mobile_number' => $dto->mobile_number ?? $applicant->mobile_number,
                'position_title' => $dto->position_title ?? $applicant->position?->title,
                'date_hired' => $dto->date_hired ?? now()->toDateString(),
                'status' => 'Active',
                'employment_type' => 'Full-Time',
            ];

            $employee = $this->employeeService->create($payload);

            if ($dto->migrate_resume) {
                $this->migrateLatestResume($applicant, $employee, $userId);
            }

            $applicant->forceFill([
                'stage' => Applicant::STAGE_HIRED,
                'last_activity_at' => now(),
            ])->save();

            $this->activityLogService->log('hired', $applicant, [
                'employee_id' => (int) $employee->employee_id,
                'employee_code' => (string) $employee->employee_code,
                'position_id' => (int) $applicant->position_id,
                'migrate_resume' => (bool) $dto->migrate_resume,
            ], 'Applicant has been hired.');

            app(\App\Services\AuditLogger::class)->log(
                'applicant.hired',
                $applicant,
                ['stage' => (string) ($applicant->getOriginal('stage') ?? '')],
                ['stage' => (string) $applicant->stage],
                [
                    'applicant_id' => (int) $applicant->id,
                    'employee_id' => (int) $employee->employee_id,
                    'employee_code' => (string) $employee->employee_code,
                    'position_id' => (int) $applicant->position_id,
                    'migrate_resume' => (bool) $dto->migrate_resume,
                ],
                'Applicant hired.'
            );

            return $employee;
        });

        return $employee;
    }

    private function nextEmployeeCode(): string
    {
        $year = now()->format('Y');
        $prefix = "EMP-{$year}-";

        $rows = DB::table('employees')
            ->select('employee_code')
            ->where('employee_code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->get();

        $max = 0;
        foreach ($rows as $row) {
            $code = (string) ($row->employee_code ?? '');
            if (!str_starts_with($code, $prefix)) {
                continue;
            }
            $suffix = substr($code, strlen($prefix));
            if (!preg_match('/^\d{4}$/', $suffix)) {
                continue;
            }
            $num = (int) $suffix;
            if ($num > $max) {
                $max = $num;
            }
        }

        $next = $max + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function migrateLatestResume(Applicant $applicant, Employee $employee, ?int $uploadedBy): void
    {
        $resume = ApplicantDocument::query()
            ->where('applicant_id', (int) $applicant->id)
            ->where('type', 'Resume')
            ->orderByDesc('id')
            ->first();

        if (!$resume) {
            return;
        }

        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
        $uuid = (string) Str::uuid();
        $newPath = "employees/{$employee->employee_id}/documents/{$uuid}.bin";

        Storage::disk($disk)->copy($resume->file_path, $newPath);

        EmployeeDocument::query()->create([
            'employee_id' => (int) $employee->employee_id,
            'type' => 'Resume',
            'original_name' => $resume->original_name,
            'file_path' => $newPath,
            'mime_type' => $resume->mime_type,
            'file_size' => $resume->file_size,
            'issue_date' => null,
            'expiry_date' => null,
            'notes' => $resume->notes,
            'uploaded_by' => $uploadedBy,
            'is_encrypted' => true,
            'encryption_algo' => $resume->encryption_algo,
            'encryption_iv' => $resume->encryption_iv,
            'encryption_tag' => $resume->encryption_tag,
            'key_version' => $resume->key_version,
        ]);
    }
}
