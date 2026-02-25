<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_company_cannot_delete_employees(): void
    {
        $company = Company::query()->create([
            'name' => 'Demo Logistics Inc',
            'slug' => 'demo-logistics-inc',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
            'is_demo' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => (int) $company->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $department = Department::query()->create([
            'company_id' => (int) $company->id,
            'name' => 'Logistics',
            'code' => 'LOG',
        ]);

        $employee = Employee::query()->create([
            'company_id' => (int) $company->id,
            'department_id' => (int) $department->department_id,
            'employee_code' => 'DLI-EMP-0001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@demo.test',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
            'created_by' => (int) $user->id,
        ]);

        $this->actingAs($user)
            ->from('/employees')
            ->withHeader('X-Inertia', 'true')
            ->delete(route('employees.destroy', $employee->employee_id))
            ->assertStatus(303)
            ->assertRedirect('/employees')
            ->assertSessionHas('error', 'Demo mode: delete actions are disabled.');

        $this->assertDatabaseHas('employees', [
            'employee_id' => (int) $employee->employee_id,
            'deleted_at' => null,
        ]);
    }

    public function test_demo_company_cannot_mutate_settings_routes(): void
    {
        $company = Company::query()->create([
            'name' => 'Demo Logistics Inc',
            'slug' => 'demo-logistics-inc',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
            'is_demo' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => (int) $company->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)
            ->from('/settings/memo-templates')
            ->withHeader('X-Inertia', 'true')
            ->post(route('settings.memo_templates.store'), [
                'name' => 'Demo Template',
                'slug' => 'demo-template',
                'description' => 'Should be blocked in demo mode',
                'body_html' => '<div>Test</div>',
                'is_active' => true,
            ])
            ->assertStatus(303)
            ->assertRedirect('/settings/memo-templates')
            ->assertSessionHas('error', 'Demo mode: settings changes are disabled.');
    }
}
