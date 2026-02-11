<?php

namespace Tests\Feature\Account;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserControllerTest extends TestCase
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
        $response = $this->get('/users');

        $response->assertStatus(200);
    }

    public function testCreate(): void
    {
        $response = $this->get('/users/create');

        $response->assertStatus(200);
    }

    public function testStore(): void
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'user_'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'password123',
            'role' => User::ROLE_HR,
        ];

        $response = $this->post('/users', $payload);

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'role' => $payload['role'],
            'deleted_at' => null,
        ]);

        $createdUser = User::query()->where('email', $payload['email'])->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'created',
            'subject_type' => User::class,
            'subject_id' => $createdUser->id,
            'description' => 'User has been created.',
        ]);
    }

    public function testEdit(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->get("/users/{$user->id}/edit");

        $response->assertStatus(200);
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'email' => 'updated_'.Str::lower(Str::random(8)).'@example.com',
            'role' => User::ROLE_HR,
            'password' => null,
        ];

        $response = $this->patch("/users/{$user->id}", $payload);

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => 'User has been updated.',
        ]);
    }

    public function testDestroy(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->delete("/users/{$user->id}");

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => auth()->id(),
            'action' => 'deleted',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => 'User has been deleted.',
        ]);
    }
}
