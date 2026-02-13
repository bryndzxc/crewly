<?php

namespace App\Services;

use App\DTO\ApplicantInterviewData;
use App\Models\Applicant;
use App\Models\ApplicantInterview;
use App\Repositories\ApplicantInterviewRepository;
use App\Repositories\ApplicantRepository;
use Illuminate\Support\Facades\DB;

class ApplicantInterviewService extends Service
{
    public function __construct(
        private readonly ApplicantInterviewRepository $applicantInterviewRepository,
        private readonly ApplicantRepository $applicantRepository,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function create(Applicant $applicant, array $validated, ?int $userId): ApplicantInterview
    {
        $dto = ApplicantInterviewData::fromArray($validated);

        return DB::transaction(function () use ($applicant, $dto, $userId) {
            $interview = $this->applicantInterviewRepository->create((int) $applicant->id, $dto, $userId);
            $this->applicantRepository->touchLastActivity($applicant);

            $this->activityLogService->log('created', $interview, [
                'applicant_id' => (int) $applicant->id,
                'scheduled_at' => $interview->scheduled_at?->format('Y-m-d H:i:s'),
                'has_notes' => ($interview->notes ?? '') !== '',
            ], 'Applicant interview has been created.');

            return $interview;
        });
    }

    public function update(Applicant $applicant, ApplicantInterview $interview, array $validated, ?int $userId): ApplicantInterview
    {
        if ((int) $interview->applicant_id !== (int) $applicant->id) {
            abort(404);
        }

        $dto = ApplicantInterviewData::fromArray($validated);

        return DB::transaction(function () use ($applicant, $interview, $dto, $userId) {
            $trackedFields = ['scheduled_at'];
            $before = $interview->only($trackedFields);

            $updated = $this->applicantInterviewRepository->update($interview, $dto, $userId);
            $this->applicantRepository->touchLastActivity($applicant);

            $this->activityLogService->logModelUpdated(
                $updated,
                $before,
                $trackedFields,
                [
                    'applicant_id' => (int) $applicant->id,
                    'has_notes' => ($updated->notes ?? '') !== '',
                ],
                'Applicant interview has been updated.'
            );

            return $updated;
        });
    }

    public function delete(Applicant $applicant, ApplicantInterview $interview): void
    {
        if ((int) $interview->applicant_id !== (int) $applicant->id) {
            abort(404);
        }

        DB::transaction(function () use ($applicant, $interview) {
            $attributes = $interview->only(['id', 'applicant_id', 'scheduled_at']);

            $this->applicantInterviewRepository->delete($interview);
            $this->applicantRepository->touchLastActivity($applicant);

            $this->activityLogService->log('deleted', $interview, [
                'attributes' => $attributes,
            ], 'Applicant interview has been deleted.');
        });
    }
}
