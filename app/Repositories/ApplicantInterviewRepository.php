<?php

namespace App\Repositories;

use App\DTO\ApplicantInterviewData;
use App\Models\ApplicantInterview;

class ApplicantInterviewRepository extends BaseRepository
{
    public function create(int $applicantId, ApplicantInterviewData $data, ?int $userId): ApplicantInterview
    {
        return ApplicantInterview::query()->create([
            'applicant_id' => $applicantId,
            'scheduled_at' => $data->scheduled_at,
            'notes' => $data->notes,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function update(ApplicantInterview $interview, ApplicantInterviewData $data, ?int $userId): ApplicantInterview
    {
        $interview->update([
            'scheduled_at' => $data->scheduled_at,
            'notes' => $data->notes,
            'updated_by' => $userId,
        ]);

        return $interview;
    }

    public function delete(ApplicantInterview $interview): void
    {
        $interview->delete();
    }
}
