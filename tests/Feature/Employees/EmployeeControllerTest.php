<?php

namespace Tests\Feature\Employees;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);
    }

    public function testIndex(): void
    {
        $response = $this->get('/employees');

        $response->assertStatus(200);
    }

    public function testCreate(): void
    {
        $response = $this->get('/employees/create');

        $response->assertStatus(200);
    }

    public function testStore(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $payload = [
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'John',
            'middle_name' => null,
            'last_name' => 'Doe',
            'suffix' => null,
            'email' => 'john.doe+'.Str::lower(Str::random(6)).'@example.com',
            'mobile_number' => '0917'.random_int(1000000, 9999999),
            'status' => 'Active',
            'position_title' => 'Developer',
            'date_hired' => '2026-02-01',
            'regularization_date' => '2026-08-01',
            'employment_type' => 'Full-Time',
            'notes' => 'Test notes',
        ];

        $response = $this->post('/employees', $payload);

        $response
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'department_id' => $payload['department_id'],
            'employee_code' => $payload['employee_code'],
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'deleted_at' => null,
        ]);

        $createdEmployee = Employee::query()->where('employee_code', $payload['employee_code'])->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'created',
            'subject_type' => Employee::class,
            'subject_id' => $createdEmployee->employee_id,
            'description' => 'Employee has been created.',
        ]);
    }

    public function testShow(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $response = $this->getJson("/employees/{$employee->employee_id}");

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'employee_id' => $employee->employee_id,
                'employee_code' => $employee->employee_code,
                'first_name' => 'Jane',
                'last_name' => 'Doe',
            ]);
    }

    public function testEdit(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $response = $this->get("/employees/{$employee->employee_id}/edit");

        $response->assertStatus(200);
    }

    public function testUpdate(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $payload = [
            'first_name' => 'Janet',
            'position_title' => 'Senior Developer',
        ];

        $response = $this->patch("/employees/{$employee->employee_id}", $payload);

        $response
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'employee_id' => $employee->employee_id,
            'first_name' => 'Janet',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'updated',
            'subject_type' => Employee::class,
            'subject_id' => $employee->employee_id,
            'description' => 'Employee has been updated.',
        ]);
    }

    public function testDestroy(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $response = $this->delete("/employees/{$employee->employee_id}");

        $response
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('employees', [
            'employee_id' => $employee->employee_id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'deleted',
            'subject_type' => Employee::class,
            'subject_id' => $employee->employee_id,
            'description' => 'Employee has been deleted.',
        ]);
    }
}
