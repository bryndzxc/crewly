<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Memo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function testCompanyAUserCannotViewCompanyBEmployee(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Company A',
            'slug' => 'company-a',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
        ]);

        $companyB = Company::query()->create([
            'name' => 'Company B',
            'slug' => 'company-b',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
        ]);

        $userA = User::factory()->create([
            'company_id' => (int) $companyA->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $deptA = Department::query()->create([
            'company_id' => (int) $companyA->id,
            'name' => 'Dept A',
            'code' => 'A'.Str::upper(Str::random(4)),
        ]);

        $deptB = Department::query()->create([
            'company_id' => (int) $companyB->id,
            'name' => 'Dept B',
            'code' => 'B'.Str::upper(Str::random(4)),
        ]);

        $employeeB = Employee::query()->create([
            'company_id' => (int) $companyB->id,
            'department_id' => (int) $deptB->department_id,
            'employee_code' => 'EMP_B_'.Str::upper(Str::random(6)),
            'first_name' => 'Berta',
            'last_name' => 'Tenant',
            'email' => 'berta.'.Str::lower(Str::random(6)).'@b.test',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $this->actingAs($userA);

        $resp = $this->getJson(route('employees.show', $employeeB->employee_id));

        // The Employee route-model binding should fail under company scoping.
        $resp->assertStatus(404);
    }

    public function testCompanyAUserCannotDownloadCompanyBMemoPdf(): void
    {
        Storage::fake('local');

        $companyA = Company::query()->create([
            'name' => 'Company A',
            'slug' => 'company-a',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
        ]);

        $companyB = Company::query()->create([
            'name' => 'Company B',
            'slug' => 'company-b',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
        ]);

        $userA = User::factory()->create([
            'company_id' => (int) $companyA->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $deptB = Department::query()->create([
            'company_id' => (int) $companyB->id,
            'name' => 'Dept B',
            'code' => 'B'.Str::upper(Str::random(4)),
        ]);

        $employeeB = Employee::query()->create([
            'company_id' => (int) $companyB->id,
            'department_id' => (int) $deptB->department_id,
            'employee_code' => 'EMP_B_'.Str::upper(Str::random(6)),
            'first_name' => 'Berta',
            'last_name' => 'Tenant',
            'email' => 'berta.'.Str::lower(Str::random(6)).'@b.test',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $path = 'private/memos/'.$employeeB->employee_id.'/seed.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 seeded');

        $memoB = Memo::query()->create([
            'company_id' => (int) $companyB->id,
            'employee_id' => (int) $employeeB->employee_id,
            'incident_id' => null,
            'memo_template_id' => null,
            'title' => 'Company B Memo',
            'body_rendered_html' => '<p>Test</p>',
            'pdf_path' => $path,
            'status' => 'generated',
            'created_by_user_id' => (int) $userA->id,
        ]);

        $this->actingAs($userA);

        $resp = $this->get(route('memos.download', $memoB->id));

        // The Memo route-model binding should fail under company scoping.
        $resp->assertStatus(404);
    }

    public function testScopedQueriesOnlyReturnCurrentCompanyData(): void
    {
        $companyA = Company::query()->create([
            'name' => 'Company A',
            'slug' => 'company-a',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
        ]);

        $companyB = Company::query()->create([
            'name' => 'Company B',
            'slug' => 'company-b',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
        ]);

        $userA = User::factory()->create([
            'company_id' => (int) $companyA->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $deptA = Department::query()->create([
            'company_id' => (int) $companyA->id,
            'name' => 'Dept A',
            'code' => 'A'.Str::upper(Str::random(4)),
        ]);

        $deptB = Department::query()->create([
            'company_id' => (int) $companyB->id,
            'name' => 'Dept B',
            'code' => 'B'.Str::upper(Str::random(4)),
        ]);

        Employee::query()->create([
            'company_id' => (int) $companyA->id,
            'department_id' => (int) $deptA->department_id,
            'employee_code' => 'EMP_A_'.Str::upper(Str::random(6)),
            'first_name' => 'Alice',
            'last_name' => 'Tenant',
            'email' => 'alice.'.Str::lower(Str::random(6)).'@a.test',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        Employee::query()->create([
            'company_id' => (int) $companyB->id,
            'department_id' => (int) $deptB->department_id,
            'employee_code' => 'EMP_B_'.Str::upper(Str::random(6)),
            'first_name' => 'Berta',
            'last_name' => 'Tenant',
            'email' => 'berta.'.Str::lower(Str::random(6)).'@b.test',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $this->actingAs($userA);

        $companyIds = Employee::query()->pluck('company_id')->unique()->values()->all();

        $this->assertSame([(int) $companyA->id], $companyIds);
        $this->assertSame(1, Employee::query()->count());
    }
}
