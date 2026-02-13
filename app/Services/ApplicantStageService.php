<?php

namespace App\Services;

use App\Models\Applicant;
use App\Repositories\ApplicantRepository;
use Illuminate\Support\Facades\DB;

class ApplicantStageService extends Service
{
    public function __construct(
        private readonly ApplicantRepository $applicantRepository,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function updateStage(Applicant $applicant, string $stage): Applicant
    {
        return DB::transaction(function () use ($applicant, $stage) {
            $from = (string) $applicant->stage;

            $updated = $this->applicantRepository->updateStage($applicant, $stage);

            $this->activityLogService->log('stage_changed', $updated, [
                'from_stage' => $from,
                'to_stage' => (string) $updated->stage,
                'position_id' => (int) $updated->position_id,
            ], 'Applicant stage has been changed.');

            app(\App\Services\AuditLogger::class)->log(
                'applicant.stage.updated',
                $updated,
                ['stage' => $from],
                ['stage' => (string) $updated->stage],
                ['position_id' => (int) $updated->position_id],
                'Applicant stage changed.'
            );

            return $updated;
        });
    }
}
