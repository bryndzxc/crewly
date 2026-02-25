<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->orderBy('id')->get(['id']);
        if ($companies->isEmpty()) {
            $companies = collect([
                Company::query()->create([
                    'name' => 'Default Company',
                    'slug' => 'default',
                    'timezone' => (string) config('app.timezone', 'Asia/Manila'),
                    'is_active' => true,
                ]),
            ]);
        }

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

        foreach ($companies as $company) {
            $createdBy = User::query()
                ->where('company_id', (int) $company->id)
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_HR])
                ->orderBy('id')
                ->value('id');

            foreach ($types as $type) {
                LeaveType::query()->updateOrCreate(
                    ['company_id' => (int) $company->id, 'code' => $type['code']],
                    array_merge($type, ['company_id' => (int) $company->id, 'created_by' => $createdBy])
                );
            }
        }
    }
}
