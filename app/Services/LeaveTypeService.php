<?php

namespace App\Services;

use App\DTO\LeaveTypeData;
use App\Models\LeaveType;
use App\Repositories\LeaveTypeRepository;
use Illuminate\Http\Request;

class LeaveTypeService extends Service
{
    public function __construct(
        private readonly LeaveTypeRepository $leaveTypeRepository,
    ) {}

    public function index(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $types = LeaveType::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->pagination($perPage);

        return [
            'types' => $types,
            'filters' => [
                'per_page' => $perPage,
            ],
        ];
    }

    public function create(array $validated, ?int $userId): LeaveType
    {
        $dto = LeaveTypeData::fromArray($validated);

        return $this->leaveTypeRepository->create($dto, $userId);
    }

    public function update(LeaveType $type, array $validated): LeaveType
    {
        $dto = LeaveTypeData::fromArray($validated);

        return $this->leaveTypeRepository->update($type, $dto);
    }

    public function editPayload(LeaveType $type): array
    {
        return [
            'type' => $type->only([
                'id',
                'name',
                'code',
                'requires_approval',
                'paid',
                'allow_half_day',
                'default_annual_credits',
                'is_active',
            ]),
        ];
    }
}
