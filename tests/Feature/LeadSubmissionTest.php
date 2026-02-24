<?php

namespace Tests\Feature;

use App\Mail\NewLeadSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_submission_validates_required_fields(): void
    {
        $this->from('/demo')->post('/leads', [])
            ->assertSessionHasErrors(['full_name', 'company_name', 'email']);
    }

    public function test_lead_submission_stores_record_and_sends_mail(): void
    {
        Mail::fake();
        config()->set('crewly.leads.admin_email', 'admin@example.test');

        $payload = [
            'full_name' => 'Juan Dela Cruz',
            'company_name' => 'ACME Logistics',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'company_size' => '11-50',
            'message' => 'Please show incident workflow.',
            'source_page' => '/demo',
        ];

        $this->from('/demo')->post('/leads', $payload)
            ->assertStatus(303)
            ->assertRedirect('/demo')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'email' => 'juan@example.com',
            'company_name' => 'ACME Logistics',
            'source_page' => '/demo',
        ]);

        Mail::assertSent(NewLeadSubmitted::class);
    }

    public function test_lead_submission_does_not_send_mail_for_demo_email(): void
    {
        Mail::fake();
        config()->set('crewly.leads.admin_email', 'admin@example.test');
        config()->set('crewly.demo.email', 'demo@crewly.test');

        $payload = [
            'full_name' => 'Crewly Demo',
            'company_name' => 'Demo Company',
            'email' => 'demo@crewly.test',
            'phone' => null,
            'company_size' => '1-10',
            'message' => 'Testing the demo flow.',
            'source_page' => '/demo',
        ];

        $this->from('/demo')->post('/leads', $payload)
            ->assertStatus(303)
            ->assertRedirect('/demo')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'email' => 'demo@crewly.test',
            'company_name' => 'Demo Company',
            'source_page' => '/demo',
        ]);

        Mail::assertNotSent(NewLeadSubmitted::class);
    }
}
