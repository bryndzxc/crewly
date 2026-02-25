<?php

namespace Tests\Feature\Developer;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\EmployeeNote;
use App\Models\Lead;
use App\Models\Memo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperDemoRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function enableDeveloperBypass(): void
    {
        config([
            'app.developer_bypass' => true,
            'app.developer_emails' => ['dev@example.com'],
        ]);
    }

    public function test_non_developer_cannot_access_demo_requests(): void
    {
        $this->enableDeveloperBypass();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->actingAs($user)
            ->get(route('developer.demo_requests.index'))
            ->assertStatus(403);
    }

    public function test_developer_can_view_demo_requests(): void
    {
        $this->enableDeveloperBypass();

        Lead::query()->create([
            'full_name' => 'Jane Doe',
            'company_name' => 'Acme Inc',
            'email' => 'jane@acme.test',
            'phone' => null,
            'company_size' => '1-10',
            'message' => 'Would love to see workflows.',
            'source_page' => '/demo',
        ]);

        $dev = User::factory()->create(['email' => 'dev@example.com']);

        $this->actingAs($dev)
            ->get(route('developer.demo_requests.index'))
            ->assertStatus(200);
    }

    public function test_developer_can_approve_demo_request_and_it_creates_company_and_hr_user(): void
    {
        $this->enableDeveloperBypass();

        $lead = Lead::query()->create([
            'full_name' => 'Jane Doe',
            'company_name' => 'Acme Inc',
            'email' => 'jane@acme.test',
            'phone' => null,
            'company_size' => '1-10',
            'message' => 'Would love to see workflows.',
            'source_page' => '/demo',
        ]);

        $dev = User::factory()->create(['email' => 'dev@example.com']);

        $this->actingAs($dev)
            ->post(route('developer.demo_requests.approve', $lead))
            ->assertStatus(303);

        $lead->refresh();
        $this->assertSame(Lead::STATUS_APPROVED, $lead->status);
        $this->assertNotNull($lead->approved_at);
        $this->assertNotNull($lead->company_id);
        $this->assertNotNull($lead->user_id);

        $company = Company::query()->findOrFail($lead->company_id);
        $this->assertSame('Acme Inc', $company->name);

        $user = User::query()->findOrFail($lead->user_id);
        $this->assertSame('jane@acme.test', $user->email);
        $this->assertSame(User::ROLE_HR, $user->role);
        $this->assertSame((int) $company->id, (int) $user->company_id);
        $this->assertTrue((bool) $user->must_change_password);

        $employeeCount = (int) Employee::withoutCompanyScope()->where('company_id', (int) $company->id)->count();
        $this->assertGreaterThanOrEqual(12, $employeeCount);
        $this->assertLessThanOrEqual(20, $employeeCount);

        $incidents = EmployeeIncident::withoutCompanyScope()->where('company_id', (int) $company->id)->get();
        $this->assertGreaterThanOrEqual(4, $incidents->count());
        $this->assertLessThanOrEqual(6, $incidents->count());

        $this->assertSame(2, $incidents->where('status', EmployeeIncident::STATUS_OPEN)->count());
        $this->assertSame(2, $incidents->where('status', EmployeeIncident::STATUS_UNDER_REVIEW)->count());
        $this->assertSame(1, $incidents->where('status', EmployeeIncident::STATUS_CLOSED)->count());

        $notesCount = (int) EmployeeNote::withoutCompanyScope()->where('company_id', (int) $company->id)->count();
        $this->assertGreaterThanOrEqual(6, $notesCount);
        $this->assertLessThanOrEqual(10, $notesCount);

        $memosCount = (int) Memo::withoutCompanyScope()->where('company_id', (int) $company->id)->count();
        $this->assertGreaterThanOrEqual(2, $memosCount);
    }

    public function test_developer_can_decline_demo_request(): void
    {
        $this->enableDeveloperBypass();

        $lead = Lead::query()->create([
            'full_name' => 'Jane Doe',
            'company_name' => 'Acme Inc',
            'email' => 'jane@acme.test',
            'phone' => null,
            'company_size' => '1-10',
            'message' => 'Would love to see workflows.',
            'source_page' => '/demo',
        ]);

        $dev = User::factory()->create(['email' => 'dev@example.com']);

        $this->actingAs($dev)
            ->post(route('developer.demo_requests.decline', $lead))
            ->assertStatus(303);

        $lead->refresh();
        $this->assertSame(Lead::STATUS_DECLINED, $lead->status);
        $this->assertNotNull($lead->declined_at);
        $this->assertNull($lead->approved_at);
        $this->assertNull($lead->company_id);
        $this->assertNull($lead->user_id);
    }
}
