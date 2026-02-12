<?php

namespace Tests\Feature\Employees;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
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
        $response = $this->get('/departments');

        $response->assertStatus(200);
    }

    public function testCreate(): void
    {
        $response = $this->get('/departments/create');

        $response->assertStatus(200);
    }

    public function testStore(): void
    {
        $payload = [
            'name' => 'Human Resources',
            'code' => 'HR_'.Str::upper(Str::random(5)),
        ];

        $response = $this->post('/departments', $payload);

        $response
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'name' => $payload['name'],
            'code' => $payload['code'],
            'deleted_at' => null,
        ]);

        $createdDepartment = Department::query()->where('code', $payload['code'])->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'created',
            'subject_type' => Department::class,
            'subject_id' => $createdDepartment->department_id,
            'description' => 'Department has been created.',
        ]);
    }

    public function testEdit(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $response = $this->get("/departments/{$department->department_id}/edit");

        $response->assertStatus(200);
    }

    public function testUpdate(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $payload = [
            'name' => 'Engineering Updated',
            'code' => 'ENGU_'.Str::upper(Str::random(4)),
        ];

        $response = $this->patch("/departments/{$department->department_id}", $payload);

        $response
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'department_id' => $department->department_id,
            'name' => $payload['name'],
            'code' => $payload['code'],
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'updated',
            'subject_type' => Department::class,
            'subject_id' => $department->department_id,
            'description' => 'Department has been updated.',
        ]);
    }

    public function testDestroy(): void
    {
        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $response = $this->delete("/departments/{$department->department_id}");

        $response
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('departments', [
            'department_id' => $department->department_id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'deleted',
            'subject_type' => Department::class,
            'subject_id' => $department->department_id,
            'description' => 'Department has been deleted.',
        ]);
    }
}
