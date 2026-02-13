<?php

namespace App\Repositories;

use App\DTO\ApplicantData;
use App\Models\Applicant;
use Illuminate\Pagination\LengthAwarePaginator;

class ApplicantRepository extends BaseRepository
{
    public function paginateForIndex(string $stage, mixed $positionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = Applicant::query()
            ->with(['position:id,title'])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id');

        if ($stage !== '') {
            $query->where('stage', $stage);
        }

        if ($positionId !== null && $positionId !== '') {
            $query->where('position_id', (int) $positionId);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(ApplicantData $data, ?int $userId): Applicant
    {
        $payload = $data->toArray();

        $payload['stage'] = $payload['stage'] ?: Applicant::STAGE_APPLIED;
        $payload['applied_at'] = $payload['applied_at'] ?: now()->toDateString();
        $payload['last_activity_at'] = now();
        $payload['created_by'] = $userId;

        return Applicant::query()->create($payload);
    }

    public function update(Applicant $applicant, ApplicantData $data): Applicant
    {
        $payload = $data->toArray();
        $payload['last_activity_at'] = now();

        $applicant->update($payload);

        return $applicant;
    }

    public function updateStage(Applicant $applicant, string $stage): Applicant
    {
        $applicant->forceFill([
            'stage' => $stage,
            'last_activity_at' => now(),
        ])->save();

        return $applicant;
    }

    public function touchLastActivity(Applicant $applicant): void
    {
        $applicant->forceFill([
            'last_activity_at' => now(),
        ])->save();
    }

    public function delete(Applicant $applicant): void
    {
        $applicant->delete();
    }
}
