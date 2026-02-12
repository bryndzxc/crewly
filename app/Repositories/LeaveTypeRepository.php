<?php

namespace App\Repositories;

use App\DTO\LeaveTypeData;
use App\Models\LeaveType;

class LeaveTypeRepository extends BaseRepository
{
    public function create(LeaveTypeData $data, ?int $userId): LeaveType
    {
        $type = new LeaveType();
        $type->fill($data->toArray());
        $type->created_by = $userId;
        $type->save();

        return $type;
    }

    public function update(LeaveType $type, LeaveTypeData $data): LeaveType
    {
        $type->fill($data->toArray());
        $type->save();

        return $type;
    }
}
