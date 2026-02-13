<?php

namespace App\Repositories;

use App\DTO\RecruitmentPositionData;
use App\Models\RecruitmentPosition;
use Illuminate\Pagination\LengthAwarePaginator;

class RecruitmentPositionRepository extends BaseRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return RecruitmentPosition::query()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(RecruitmentPositionData $data, ?int $userId): RecruitmentPosition
    {
        $position = new RecruitmentPosition();
        $position->fill($data->toArray());
        $position->created_by = $userId;
        $position->save();

        return $position;
    }

    public function update(RecruitmentPosition $position, RecruitmentPositionData $data): RecruitmentPosition
    {
        $position->fill($data->toArray());
        $position->save();

        return $position;
    }

    public function delete(RecruitmentPosition $position): void
    {
        $position->delete();
    }
}
