<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\DeveloperLeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConvertDemoCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_can_convert_demo_company_to_real_and_purge_seeded_data(): void
    {
        config()->set('app.developer_bypass', true);
        config()->set('app.developer_emails', ['dev@crewly.test']);

        $company = Company::query()->create([
            'name' => 'Demo Logistics Inc',
            'slug' => 'demo-logistics-inc',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
            'is_demo' => true,
        ]);

        $companyUser = User::factory()->create([
            'company_id' => (int) $company->id,
            'role' => User::ROLE_HR,
        ]);

        // Seed demo/sample data using the same path as demo approvals.
        app(DeveloperLeadService::class)->seedDemoCompanyData($company, $companyUser);

        $this->assertTrue(DB::table('employees')->where('company_id', (int) $company->id)->count() > 0);
        $this->assertTrue(DB::table('memos')->where('company_id', (int) $company->id)->count() > 0);

        $leaveTypeCountBefore = (int) DB::table('leave_types')->where('company_id', (int) $company->id)->count();
        $memoTemplateCountBefore = (int) DB::table('memo_templates')->where('company_id', (int) $company->id)->count();

        $this->assertTrue($leaveTypeCountBefore > 0);

        $developer = User::factory()->create([
            'email' => 'dev@crewly.test',
            'role' => User::ROLE_ADMIN,
            'company_id' => null,
        ]);

        $this->actingAs($developer)
            ->post(route('developer.companies.convert_from_demo', $company))
            ->assertStatus(303);

        $this->assertDatabaseHas('companies', [
            'id' => (int) $company->id,
            'is_demo' => 0,
            'is_active' => 1,
        ]);

        // Seeded data cleared.
        $this->assertSame(0, (int) DB::table('employees')->where('company_id', (int) $company->id)->count());
        $this->assertSame(0, (int) DB::table('employee_incidents')->where('company_id', (int) $company->id)->count());
        $this->assertSame(0, (int) DB::table('employee_notes')->where('company_id', (int) $company->id)->count());
        $this->assertSame(0, (int) DB::table('memos')->where('company_id', (int) $company->id)->count());

        // Leave types and memo templates are necessary for real accounts.
        $this->assertSame($leaveTypeCountBefore, (int) DB::table('leave_types')->where('company_id', (int) $company->id)->count());
        $this->assertSame($memoTemplateCountBefore, (int) DB::table('memo_templates')->where('company_id', (int) $company->id)->count());

        // Company user remains.
        $this->assertDatabaseHas('users', [
            'id' => (int) $companyUser->id,
            'company_id' => (int) $company->id,
        ]);
    }
}
