<?php

namespace Database\Seeders;

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
        $createdBy = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->value('id');

        if (!$createdBy) {
            $createdBy = User::query()->orderBy('id')->value('id');
        }

        $departmentNameByCode = Department::query()
            ->pluck('name', 'code')
            ->all();

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

        foreach ($positions as $pos) {
            $department = $departmentNameByCode[$pos['department_code']] ?? $pos['department_code'];

            RecruitmentPosition::query()->updateOrCreate(
                [
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
