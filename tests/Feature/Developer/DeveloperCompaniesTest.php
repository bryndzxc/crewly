<?php

namespace Tests\Feature\Developer;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperCompaniesTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_developer_cannot_access_developer_companies(): void
    {
        config([
            'app.developer_bypass' => true,
            'app.developer_emails' => ['dev@example.com'],
        ]);

        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->actingAs($user)
            ->get(route('developer.companies.index'))
            ->assertStatus(403);
    }

    public function test_developer_can_view_companies_list(): void
    {
        config([
            'app.developer_bypass' => true,
            'app.developer_emails' => ['dev@example.com'],
        ]);

        $dev = User::factory()->create(['email' => 'dev@example.com']);

        $this->actingAs($dev)
            ->get(route('developer.companies.index'))
            ->assertStatus(200);
    }

    public function test_developer_can_create_company_with_initial_user(): void
    {
        config([
            'app.developer_bypass' => true,
            'app.developer_emails' => ['dev@example.com'],
        ]);

        $dev = User::factory()->create(['email' => 'dev@example.com']);

        $payload = [
            'company' => [
                'name' => 'Acme Test Co',
                'slug' => '',
                'timezone' => 'Asia/Manila',
                'is_active' => true,
            ],
            'user' => [
                'name' => 'Acme Manager',
                'email' => 'manager@acme.test',
                'password' => 'password123',
                'role' => 'manager',
            ],
        ];

        $this->actingAs($dev)
            ->post(route('developer.companies.store'), $payload)
            ->assertStatus(303)
            ->assertSessionHas('success');

        $company = Company::query()->where('name', 'Acme Test Co')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'manager@acme.test',
            'role' => 'manager',
        ]);
    }
}
