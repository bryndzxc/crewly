<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\RecruitmentPosition;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecruitmentPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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

        $positions = [
            ['title' => 'Recruitment Specialist', 'department_code' => 'HR', 'location' => 'Hybrid', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'HR Generalist', 'department_code' => 'HR', 'location' => 'On-site', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Software Engineer', 'department_code' => 'ENG', 'location' => 'Remote', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Senior Software Engineer', 'department_code' => 'ENG', 'location' => 'Remote', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'QA Engineer', 'department_code' => 'QA', 'location' => 'Hybrid', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Product Manager', 'department_code' => 'PROD', 'location' => 'Hybrid', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'UI/UX Designer', 'department_code' => 'PROD', 'location' => 'Remote', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Customer Support Representative', 'department_code' => 'CS', 'location' => 'On-site', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Sales Associate', 'department_code' => 'SALES', 'location' => 'On-site', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Marketing Specialist', 'department_code' => 'MKT', 'location' => 'Hybrid', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Finance Analyst', 'department_code' => 'FIN', 'location' => 'On-site', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'IT Support Specialist', 'department_code' => 'IT', 'location' => 'On-site', 'status' => RecruitmentPosition::STATUS_OPEN],
            ['title' => 'Data Analyst', 'department_code' => 'DATA', 'location' => 'Hybrid', 'status' => RecruitmentPosition::STATUS_CLOSED],
        ];

        foreach ($companies as $company) {
            $createdBy = User::query()
                ->where('company_id', (int) $company->id)
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('id')
                ->value('id');

            if (!$createdBy) {
                $createdBy = User::query()->where('company_id', (int) $company->id)->orderBy('id')->value('id');
            }

            $departmentNameByCode = Department::query()
                ->where('company_id', (int) $company->id)
                ->pluck('name', 'code')
                ->all();

            foreach ($positions as $pos) {
                $department = $departmentNameByCode[$pos['department_code']] ?? $pos['department_code'];

                RecruitmentPosition::query()->updateOrCreate(
                    [
                        'company_id' => (int) $company->id,
                        'title' => $pos['title'],
                        'department' => $department,
                        'location' => $pos['location'],
                    ],
                    [
                        'status' => $pos['status'],
                        'created_by' => $createdBy,
                    ]
                );
            }
        }
    }
}
