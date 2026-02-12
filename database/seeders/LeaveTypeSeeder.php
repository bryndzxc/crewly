<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $createdBy = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_HR])
            ->orderBy('id')
            ->value('id');

        $types = [
            [
                'code' => 'VL',
                'name' => 'Vacation Leave',
                'requires_approval' => true,
                'paid' => true,
                'allow_half_day' => true,
                'default_annual_credits' => 15,
                'is_active' => true,
            ],
            [
                'code' => 'SL',
                'name' => 'Sick Leave',
                'requires_approval' => true,
                'paid' => true,
                'allow_half_day' => true,
                'default_annual_credits' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'EL',
                'name' => 'Emergency Leave',
                'requires_approval' => true,
                'paid' => true,
                'allow_half_day' => true,
                'default_annual_credits' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'UL',
                'name' => 'Unpaid Leave',
                'requires_approval' => true,
                'paid' => false,
                'allow_half_day' => true,
                'default_annual_credits' => null,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::query()->updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, ['created_by' => $createdBy])
            );
        }
    }
}
