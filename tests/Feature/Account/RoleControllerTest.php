<?php

namespace Tests\Feature\Account;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);
    }

    public function testIndex(): void
    {
        $response = $this->get('/roles');

        $response->assertStatus(200);
    }

    public function testCreate(): void
    {
        $response = $this->get('/roles/create');

        $response->assertStatus(200);
    }

    public function testStore(): void
    {
        $payload = [
            'name' => 'QA Role',
            'key' => 'qa_'.Str::lower(Str::random(8)),
        ];

        $response = $this->post('/roles', $payload);

        $response
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'key' => $payload['key'],
            'name' => $payload['name'],
            'deleted_at' => null,
        ]);

        $createdRole = Role::query()->where('key', $payload['key'])->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'created',
            'subject_type' => Role::class,
            'subject_id' => $createdRole->id,
            'description' => 'Role has been created.',
        ]);
    }

    public function testEdit(): void
    {
        $role = Role::query()->create([
            'key' => 'role_'.Str::lower(Str::random(8)),
            'name' => 'Some Role',
        ]);

        $response = $this->get("/roles/{$role->id}/edit");

        $response->assertStatus(200);
    }

    public function testUpdate(): void
    {
        $role = Role::query()->create([
            'key' => 'role_'.Str::lower(Str::random(8)),
            'name' => 'Some Role',
        ]);

        $payload = [
            'name' => 'Updated Role',
            'key' => 'updated_'.Str::lower(Str::random(8)),
        ];

        $response = $this->patch("/roles/{$role->id}", $payload);

        $response
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'key' => $payload['key'],
            'name' => $payload['name'],
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'updated',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
            'description' => 'Role has been updated.',
        ]);
    }

    public function testDestroy(): void
    {
        $role = Role::query()->create([
            'key' => 'role_'.Str::lower(Str::random(8)),
            'name' => 'Some Role',
        ]);

        $response = $this->delete("/roles/{$role->id}");

        $response
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('roles', [
            'id' => $role->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'deleted',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
            'description' => 'Role has been deleted.',
        ]);
    }
}
