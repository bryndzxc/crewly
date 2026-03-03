<?php

namespace Tests\Feature\Billing;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionStatusTransitionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_becomes_past_due_when_next_billing_passed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));

        /** @var Company $company */
        $company = Company::factory()->create([
            'subscription_status' => Company::SUB_ACTIVE,
            'next_billing_at' => Carbon::now()->copy()->subDay(),
            'grace_days' => 7,
        ]);

        $this->artisan('billing:sync-subscriptions')->assertExitCode(0);

        $company->refresh();
        $this->assertSame(Company::SUB_PAST_DUE, $company->subscription_status);
    }

    public function test_active_company_with_future_next_billing_stays_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));

        /** @var Company $company */
        $company = Company::factory()->create([
            'subscription_status' => Company::SUB_ACTIVE,
            'next_billing_at' => Carbon::now()->copy()->addDay(),
            'grace_days' => 7,
        ]);

        $this->artisan('billing:sync-subscriptions')->assertExitCode(0);

        $company->refresh();
        $this->assertSame(Company::SUB_ACTIVE, $company->subscription_status);
    }

    public function test_past_due_company_becomes_suspended_after_grace_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));

        /** @var Company $company */
        $company = Company::factory()->create([
            'subscription_status' => Company::SUB_PAST_DUE,
            // next billing was 10 days ago, grace=7 => suspend.
            'next_billing_at' => Carbon::now()->copy()->subDays(10),
            'grace_days' => 7,
        ]);

        $this->artisan('billing:sync-subscriptions')->assertExitCode(0);

        $company->refresh();
        $this->assertSame(Company::SUB_SUSPENDED, $company->subscription_status);
    }

    public function test_past_due_company_within_grace_period_stays_past_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));

        /** @var Company $company */
        $company = Company::factory()->create([
            'subscription_status' => Company::SUB_PAST_DUE,
            // next billing was 3 days ago, grace=7 => still past_due.
            'next_billing_at' => Carbon::now()->copy()->subDays(3),
            'grace_days' => 7,
        ]);

        $this->artisan('billing:sync-subscriptions')->assertExitCode(0);

        $company->refresh();
        $this->assertSame(Company::SUB_PAST_DUE, $company->subscription_status);
    }
}
