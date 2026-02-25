<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
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

        $departments = [
            ['name' => 'Executive', 'code' => 'EXEC'],
            ['name' => 'Administration', 'code' => 'ADMIN'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Accounting', 'code' => 'ACCT'],
            ['name' => 'Payroll', 'code' => 'PAY'],
            ['name' => 'Legal', 'code' => 'LEGAL'],
            ['name' => 'Compliance', 'code' => 'COMP'],
            ['name' => 'Procurement', 'code' => 'PROC'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Customer Support', 'code' => 'CS'],
            ['name' => 'Sales', 'code' => 'SALES'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'Product', 'code' => 'PROD'],
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Quality Assurance', 'code' => 'QA'],
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Information Security', 'code' => 'INFOSEC'],
            ['name' => 'Data', 'code' => 'DATA'],
            ['name' => 'Research & Development', 'code' => 'RND'],
            ['name' => 'Project Management', 'code' => 'PMO'],
            ['name' => 'Business Development', 'code' => 'BD'],
            ['name' => 'Training & Development', 'code' => 'LND'],
            ['name' => 'Facilities', 'code' => 'FAC'],
            ['name' => 'Logistics', 'code' => 'LOG'],
        ];

        foreach ($companies as $company) {
            foreach ($departments as $dept) {
                $department = Department::withTrashed()->firstOrNew([
                    'company_id' => (int) $company->id,
                    'code' => $dept['code'],
                ]);

                $department->fill([
                    'company_id' => (int) $company->id,
                    'name' => $dept['name'],
                    'code' => $dept['code'],
                ]);

                $department->save();

                if (method_exists($department, 'restore') && $department->trashed()) {
                    $department->restore();
                }
            }
        }
    }

}
