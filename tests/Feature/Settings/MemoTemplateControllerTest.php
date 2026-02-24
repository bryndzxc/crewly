<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemoTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Make role-based Gate checks deterministic.
        config()->set('app.developer_bypass', false);
        config()->set('app.developer_emails', []);
    }

    public function testAdminCanViewIndexCreateAndEditPages(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);

        $template = MemoTemplate::query()->create([
            'company_id' => null,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'description' => 'Desc',
            'body_html' => '<p>Hello</p>',
            'is_active' => true,
            'is_system' => false,
            'created_by_user_id' => $admin->id,
        ]);

        $this->get(route('settings.memo_templates.index'))->assertStatus(200);
        $this->get(route('settings.memo_templates.create'))->assertStatus(200);
        $this->get(route('settings.memo_templates.edit', $template->id))->assertStatus(200);
    }

    public function testHrCanCreateUpdateAndToggleTemplate(): void
    {
        $hr = User::factory()->create([
            'role' => User::ROLE_HR,
            'email' => 'hr@example.com',
        ]);

        $this->actingAs($hr);

        $payload = [
            'name' => 'Notice to Explain',
            'slug' => 'nte-'.Str::lower(Str::random(6)),
            'description' => 'Test',
            'body_html' => '<script>alert(1)</script><p onclick="x()">Hi {{employee_name}}</p><a href="javascript:alert(1)">x</a>',
            'is_active' => true,
        ];

        $resp = $this->post(route('settings.memo_templates.store'), $payload);
        $resp->assertStatus(303);

        $template = MemoTemplate::query()->where('slug', $payload['slug'])->firstOrFail();

        // Sanitization expectations.
        $this->assertStringNotContainsString('<script', (string) $template->body_html);
        $this->assertStringNotContainsString('onclick', (string) $template->body_html);
        $this->assertStringNotContainsString('javascript:', (string) $template->body_html);
        $this->assertStringContainsString('href="#"', (string) $template->body_html);

        $updatePayload = [
            'name' => 'Updated NTE',
            'slug' => $template->slug,
            'description' => 'Updated',
            'body_html' => '<p>Updated</p>',
            'is_active' => false,
        ];

        $this->put(route('settings.memo_templates.update', $template->id), $updatePayload)
            ->assertStatus(303)
            ->assertSessionHas('success');

        $template->refresh();
        $this->assertSame('Updated NTE', $template->name);
        $this->assertFalse((bool) $template->is_active);

        $this->patch(route('settings.memo_templates.toggle', $template->id))
            ->assertStatus(303)
            ->assertSessionHas('success');

        $template->refresh();
        $this->assertTrue((bool) $template->is_active);
    }

    public function testManagerAndEmployeeCannotAccessMemoTemplateSettings(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'email' => 'manager@example.com',
        ]);

        $employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'email' => 'employee@example.com',
        ]);

        foreach ([$manager, $employee] as $user) {
            $this->actingAs($user);

            $this->get(route('settings.memo_templates.index'))->assertStatus(403);
            $this->get(route('settings.memo_templates.create'))->assertStatus(403);
            $this->post(route('settings.memo_templates.store'), [
                'name' => 'X',
                'slug' => 'x-'.Str::lower(Str::random(6)),
                'body_html' => '<p>X</p>',
            ])->assertStatus(403);
        }
    }

    public function testStoreValidatesRequiredFieldsAndUniqueSlug(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);

        $this->post(route('settings.memo_templates.store'), [
            'name' => '',
            'slug' => '',
            'body_html' => '',
        ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['name', 'body_html']);

        MemoTemplate::query()->create([
            'company_id' => null,
            'name' => 'Existing',
            'slug' => 'duplicate-slug',
            'body_html' => '<p>Existing</p>',
            'is_active' => true,
            'is_system' => false,
            'created_by_user_id' => $admin->id,
        ]);

        $this->post(route('settings.memo_templates.store'), [
            'name' => 'New',
            'slug' => 'duplicate-slug',
            'body_html' => '<p>New</p>',
        ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['slug']);
    }
}
