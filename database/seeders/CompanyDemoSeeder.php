<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $timezone = (string) config('app.timezone', 'Asia/Manila');

        $companyA = Company::query()->updateOrCreate(
            ['slug' => 'demo-logistics-inc'],
            [
                'name' => 'Demo Logistics Inc',
                'timezone' => $timezone,
                'is_active' => true,
            ]
        );

        $companyB = Company::query()->updateOrCreate(
            ['slug' => 'sample-construction-co'],
            [
                'name' => 'Sample Construction Co',
                'timezone' => $timezone,
                'is_active' => true,
            ]
        );

        $password = Hash::make('password');

        $demoManager = User::withTrashed()->updateOrCreate(
            ['email' => 'demo.manager@crewly.test'],
            [
                'company_id' => (int) $companyA->id,
                'name' => 'Demo Manager',
                'role' => User::ROLE_MANAGER,
                'password' => $password,
                'email_verified_at' => now(),
                'must_change_password' => false,
                'chat_sound_enabled' => true,
                'deleted_at' => null,
            ]
        );

        $sampleManager = User::withTrashed()->updateOrCreate(
            ['email' => 'sample.manager@crewly.test'],
            [
                'company_id' => (int) $companyB->id,
                'name' => 'Sample Manager',
                'role' => User::ROLE_MANAGER,
                'password' => $password,
                'email_verified_at' => now(),
                'must_change_password' => false,
                'chat_sound_enabled' => true,
                'deleted_at' => null,
            ]
        );

        // Minimal per-company department so employee creation doesn't depend on other seeders.
        $deptA = Department::withTrashed()->updateOrCreate(
            ['company_id' => (int) $companyA->id, 'code' => 'LOG'],
            ['company_id' => (int) $companyA->id, 'name' => 'Logistics', 'code' => 'LOG']
        );

        $deptB = Department::withTrashed()->updateOrCreate(
            ['company_id' => (int) $companyB->id, 'code' => 'CONST'],
            ['company_id' => (int) $companyB->id, 'name' => 'Construction', 'code' => 'CONST']
        );

        $employeesA = [
            ['employee_code' => 'DLI-EMP-0001', 'first_name' => 'Alice', 'last_name' => 'Santos', 'email' => 'alice.santos@demo-logistics.test'],
            ['employee_code' => 'DLI-EMP-0002', 'first_name' => 'Mark', 'last_name' => 'Reyes', 'email' => 'mark.reyes@demo-logistics.test'],
        ];

        $employeesB = [
            ['employee_code' => 'SCC-EMP-0001', 'first_name' => 'Bea', 'last_name' => 'Cruz', 'email' => 'bea.cruz@sample-construction.test'],
            ['employee_code' => 'SCC-EMP-0002', 'first_name' => 'Jon', 'last_name' => 'Dela Rosa', 'email' => 'jon.delarosa@sample-construction.test'],
        ];

        $createdEmployeesA = [];
        foreach ($employeesA as $payload) {
            $createdEmployeesA[] = Employee::withTrashed()->updateOrCreate(
                ['company_id' => (int) $companyA->id, 'employee_code' => $payload['employee_code']],
                [
                    'company_id' => (int) $companyA->id,
                    'department_id' => (int) $deptA->department_id,
                    'employee_code' => $payload['employee_code'],
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'email' => $payload['email'],
                    'status' => 'Active',
                    'employment_type' => 'Full-Time',
                    'created_by' => (int) $demoManager->id,
                    'deleted_at' => null,
                ]
            );
        }

        $createdEmployeesB = [];
        foreach ($employeesB as $payload) {
            $createdEmployeesB[] = Employee::withTrashed()->updateOrCreate(
                ['company_id' => (int) $companyB->id, 'employee_code' => $payload['employee_code']],
                [
                    'company_id' => (int) $companyB->id,
                    'department_id' => (int) $deptB->department_id,
                    'employee_code' => $payload['employee_code'],
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'email' => $payload['email'],
                    'status' => 'Active',
                    'employment_type' => 'Full-Time',
                    'created_by' => (int) $sampleManager->id,
                    'deleted_at' => null,
                ]
            );
        }

        // Incidents (distinct per company)
        $incidentA = EmployeeIncident::query()->updateOrCreate(
            ['company_id' => (int) $companyA->id, 'employee_id' => (int) $createdEmployeesA[0]->employee_id, 'incident_date' => '2026-02-10'],
            [
                'company_id' => (int) $companyA->id,
                'employee_id' => (int) $createdEmployeesA[0]->employee_id,
                'category' => 'Late Delivery',
                'incident_date' => '2026-02-10',
                'description' => 'Delivery was delayed beyond SLA.',
                'status' => EmployeeIncident::STATUS_OPEN,
                'created_by' => (int) $demoManager->id,
            ]
        );

        $incidentB = EmployeeIncident::query()->updateOrCreate(
            ['company_id' => (int) $companyB->id, 'employee_id' => (int) $createdEmployeesB[0]->employee_id, 'incident_date' => '2026-02-12'],
            [
                'company_id' => (int) $companyB->id,
                'employee_id' => (int) $createdEmployeesB[0]->employee_id,
                'category' => 'Safety Violation',
                'incident_date' => '2026-02-12',
                'description' => 'PPE was not worn at site.',
                'status' => EmployeeIncident::STATUS_OPEN,
                'created_by' => (int) $sampleManager->id,
            ]
        );

        // Memo templates (at least 1 per company)
        $templateA = MemoTemplate::query()->updateOrCreate(
            ['company_id' => (int) $companyA->id, 'slug' => 'demo-incident-memo'],
            [
                'company_id' => (int) $companyA->id,
                'name' => 'Demo Incident Memo',
                'slug' => 'demo-incident-memo',
                'description' => 'Seeded memo template for Demo Logistics Inc',
                'body_html' => '<div><p><strong>{{company_name}}</strong></p><p>{{memo_date}}</p><p>{{employee_name}}</p><p>{{incident_description}}</p></div>',
                'is_active' => true,
                'is_system' => false,
                'created_by_user_id' => (int) $demoManager->id,
            ]
        );

        $templateB = MemoTemplate::query()->updateOrCreate(
            ['company_id' => (int) $companyB->id, 'slug' => 'sample-incident-memo'],
            [
                'company_id' => (int) $companyB->id,
                'name' => 'Sample Incident Memo',
                'slug' => 'sample-incident-memo',
                'description' => 'Seeded memo template for Sample Construction Co',
                'body_html' => '<div><p><strong>{{company_name}}</strong></p><p>{{memo_date}}</p><p>{{employee_name}}</p><p>{{incident_description}}</p></div>',
                'is_active' => true,
                'is_system' => false,
                'created_by_user_id' => (int) $sampleManager->id,
            ]
        );

        // Memos (at least 1 per company) with on-disk PDF for download testing.
        $pathA = 'memos/seed/' . $companyA->slug . '/' . Str::random(12) . '.pdf';
        $pathB = 'memos/seed/' . $companyB->slug . '/' . Str::random(12) . '.pdf';

        Storage::disk('local')->put($pathA, "%PDF-1.4\n% Crewly seeded memo (Company A)\n");
        Storage::disk('local')->put($pathB, "%PDF-1.4\n% Crewly seeded memo (Company B)\n");

        Memo::query()->updateOrCreate(
            ['company_id' => (int) $companyA->id, 'pdf_path' => $pathA],
            [
                'company_id' => (int) $companyA->id,
                'employee_id' => (int) $createdEmployeesA[0]->employee_id,
                'incident_id' => (int) $incidentA->id,
                'memo_template_id' => (int) $templateA->id,
                'title' => 'Demo Logistics Memo',
                'body_rendered_html' => '<div>Demo memo</div>',
                'pdf_path' => $pathA,
                'status' => 'generated',
                'created_by_user_id' => (int) $demoManager->id,
            ]
        );

        Memo::query()->updateOrCreate(
            ['company_id' => (int) $companyB->id, 'pdf_path' => $pathB],
            [
                'company_id' => (int) $companyB->id,
                'employee_id' => (int) $createdEmployeesB[0]->employee_id,
                'incident_id' => (int) $incidentB->id,
                'memo_template_id' => (int) $templateB->id,
                'title' => 'Sample Construction Memo',
                'body_rendered_html' => '<div>Sample memo</div>',
                'pdf_path' => $pathB,
                'status' => 'generated',
                'created_by_user_id' => (int) $sampleManager->id,
            ]
        );
    }
}
