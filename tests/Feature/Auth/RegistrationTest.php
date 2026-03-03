<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Lead;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'full_name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'test@example.com',
            'phone' => '+63 900 000 0000',
            'employee_count_range' => '1-20',
            'industry' => 'Construction',
            'current_process' => 'Mostly spreadsheets and paper forms.',
            'biggest_pain' => 'Tracking employee incidents and attendance.',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/register');

        $this->assertDatabaseHas('leads', [
            'email' => 'test@example.com',
            'lead_type' => Lead::TYPE_ACCESS,
            'status' => Lead::STATUS_PENDING,
        ]);
    }
}
