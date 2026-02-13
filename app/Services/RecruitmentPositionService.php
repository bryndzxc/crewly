<?php

namespace App\Services;

use App\DTO\RecruitmentPositionData;
use App\Models\RecruitmentPosition;
use App\Repositories\RecruitmentPositionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecruitmentPositionService extends Service
{
    public function __construct(
        private readonly RecruitmentPositionRepository $recruitmentPositionRepository,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): array
    {
        return [
            'positions' => $this->recruitmentPositionRepository->paginate(15),
        ];
    }

    public function create(array $validated, ?int $userId): RecruitmentPosition
    {
        $dto = RecruitmentPositionData::fromArray($validated);

        return DB::transaction(function () use ($dto, $userId, $validated) {
            $position = $this->recruitmentPositionRepository->create($dto, $userId);

            $this->activityLogService->log('created', $position, [
                'attributes' => $validated,
            ], 'Recruitment position has been created.');

            return $position;
        });
    }

    public function update(RecruitmentPosition $position, array $validated): RecruitmentPosition
    {
        $dto = RecruitmentPositionData::fromArray($validated);

        return DB::transaction(function () use ($position, $dto, $validated) {
            $trackedFields = ['title', 'department', 'location', 'status'];
            $before = $position->only($trackedFields);

            $updated = $this->recruitmentPositionRepository->update($position, $dto);

            $this->activityLogService->logModelUpdated(
                $updated,
                $before,
                $trackedFields,
                ['attributes' => $validated],
                'Recruitment position has been updated.'
            );

            return $updated;
        });
    }

    public function editPayload(RecruitmentPosition $position): array
    {
        return [
            'position' => $position->only(['id', 'title', 'department', 'location', 'status']),
        ];
    }

    public function delete(RecruitmentPosition $position): void
    {
        DB::transaction(function () use ($position) {
            $attributes = $position->only(['id', 'title', 'department', 'location', 'status']);

            $this->recruitmentPositionRepository->delete($position);

            $this->activityLogService->log('deleted', $position, [
                'attributes' => $attributes,
            ], 'Recruitment position has been deleted.');
        });
    }
}
