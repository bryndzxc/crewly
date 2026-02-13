<?php

namespace App\Services;

use App\DTO\ApplicantData;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\ApplicantInterview;
use App\Models\Department;
use App\Models\RecruitmentPosition;
use App\Repositories\ApplicantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ApplicantService extends Service
{
    public function __construct(
        private readonly ApplicantRepository $applicantRepository,
        private readonly ActivityLogService $activityLogService,
        private readonly ApplicantDocumentService $applicantDocumentService,
    ) {}

    public function index(Request $request): array
    {
        $stage = trim((string) $request->query('stage', ''));
        $positionId = $request->query('position_id');

        return [
            'applicants' => $this->applicantRepository->paginateForIndex($stage, $positionId, 15),
            'positions' => RecruitmentPosition::query()
                ->orderBy('title')
                ->get(['id', 'title', 'status']),
            'filters' => [
                'stage' => $stage,
                'position_id' => $positionId,
            ],
            'stages' => Applicant::stages(),
        ];
    }

    public function createPayload(): array
    {
        return [
            'positions' => RecruitmentPosition::query()
                ->orderBy('title')
                ->get(['id', 'title', 'status']),
            'stages' => Applicant::stages(),
        ];
    }

    public function create(array $validated, ?int $userId, ?UploadedFile $resume = null): Applicant
    {
        $dto = ApplicantData::fromArray($validated);

        return DB::transaction(function () use ($dto, $userId, $resume) {
            $applicant = $this->applicantRepository->create($dto, $userId);

            $this->activityLogService->log('created', $applicant, [
                'position_id' => (int) $applicant->position_id,
                'stage' => (string) $applicant->stage,
                'source' => $applicant->source,
                'expected_salary' => $applicant->expected_salary,
                'applied_at' => $applicant->applied_at?->format('Y-m-d'),
            ], 'Applicant has been created.');

            app(\App\Services\AuditLogger::class)->log(
                'applicant.created',
                $applicant,
                [],
                [
                    'applicant_id' => (int) $applicant->id,
                    'position_id' => (int) $applicant->position_id,
                    'stage' => (string) $applicant->stage,
                    'source' => (string) ($applicant->source ?? ''),
                    'expected_salary' => $applicant->expected_salary,
                    'applied_at' => $applicant->applied_at?->format('Y-m-d'),
                ],
                [],
                'Applicant created.'
            );

            if ($resume) {
                $this->applicantDocumentService->uploadMany(
                    $applicant,
                    ['type' => 'Resume', 'notes' => null],
                    [$resume],
                    $userId
                );
            }

            return $applicant;
        });
    }

    public function showPayload(Request $request, Applicant $applicant): array
    {
        $applicant->load(['position:id,title']);

        $documents = ApplicantDocument::query()
            ->where('applicant_id', (int) $applicant->id)
            ->orderByDesc('id')
            ->get([
                'id',
                'applicant_id',
                'type',
                'original_name',
                'mime_type',
                'file_path',
                'file_size',
                'notes',
                'uploaded_by',
                'created_at',
            ]);

        $interviews = ApplicantInterview::query()
            ->where('applicant_id', (int) $applicant->id)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'applicant_id',
                'scheduled_at',
                'notes',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ]);

        return [
            'applicant' => [
                'id' => $applicant->id,
                'position_id' => $applicant->position_id,
                'position' => $applicant->position?->only(['id', 'title']),
                'first_name' => $applicant->first_name,
                'middle_name' => $applicant->middle_name,
                'last_name' => $applicant->last_name,
                'suffix' => $applicant->suffix,
                'email' => $applicant->email,
                'mobile_number' => $applicant->mobile_number,
                'source' => $applicant->source,
                'stage' => $applicant->stage,
                'expected_salary' => $applicant->expected_salary,
                'notes' => $applicant->notes,
                'applied_at' => $applicant->applied_at?->format('Y-m-d'),
                'last_activity_at' => $applicant->last_activity_at?->format('Y-m-d H:i:s'),
                'created_at' => $applicant->created_at?->format('Y-m-d H:i:s'),
            ],
            'documents' => $documents,
            'interviews' => $interviews,
            'positions' => RecruitmentPosition::query()
                ->orderBy('title')
                ->get(['id', 'title', 'status']),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
            'stages' => Applicant::stages(),
        ];
    }

    public function editPayload(Applicant $applicant): array
    {
        return [
            'applicant' => [
                'id' => $applicant->id,
                'position_id' => $applicant->position_id,
                'first_name' => $applicant->first_name,
                'middle_name' => $applicant->middle_name,
                'last_name' => $applicant->last_name,
                'suffix' => $applicant->suffix,
                'email' => $applicant->email,
                'mobile_number' => $applicant->mobile_number,
                'source' => $applicant->source,
                'stage' => $applicant->stage,
                'expected_salary' => $applicant->expected_salary,
                'notes' => $applicant->notes,
                'applied_at' => $applicant->applied_at?->format('Y-m-d'),
            ],
            'positions' => RecruitmentPosition::query()
                ->orderBy('title')
                ->get(['id', 'title', 'status']),
            'stages' => Applicant::stages(),
        ];
    }

    public function update(Applicant $applicant, array $validated): Applicant
    {
        $dto = ApplicantData::fromArray($validated);

        return DB::transaction(function () use ($applicant, $dto) {
            $trackedFields = ['position_id', 'source', 'stage', 'expected_salary', 'applied_at'];
            $before = $applicant->only($trackedFields);

            $updated = $this->applicantRepository->update($applicant, $dto);

            $after = $updated->only($trackedFields);
            app(\App\Services\AuditLogger::class)->log(
                'applicant.updated',
                $updated,
                $before,
                $after,
                ['applicant_id' => (int) $updated->id],
                'Applicant updated.'
            );

            $this->activityLogService->logModelUpdated(
                $updated,
                $before,
                $trackedFields,
                [
                    'position_id' => (int) $updated->position_id,
                    'stage' => (string) $updated->stage,
                ],
                'Applicant has been updated.'
            );

            return $updated;
        });
    }

    public function delete(Applicant $applicant): void
    {
        DB::transaction(function () use ($applicant) {
            $attributes = $applicant->only(['id', 'position_id', 'stage', 'source', 'expected_salary', 'applied_at']);

            app(\App\Services\AuditLogger::class)->log(
                'applicant.deleted',
                $applicant,
                $attributes,
                [],
                [],
                'Applicant deleted.'
            );

            $this->applicantRepository->delete($applicant);

            $this->activityLogService->log('deleted', $applicant, [
                'attributes' => $attributes,
            ], 'Applicant has been deleted.');
        });
    }
}
