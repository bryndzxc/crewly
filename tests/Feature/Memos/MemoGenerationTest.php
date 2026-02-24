<?php

namespace Tests\Feature\Memos;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use App\Services\MemoPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemoGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testAdminCanPreviewAndGenerateMemoForIncident(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);

        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
            'position_title' => 'Developer',
        ]);

        $incident = EmployeeIncident::query()->create([
            'employee_id' => (int) $employee->employee_id,
            'category' => 'Conduct',
            'incident_date' => '2026-02-24',
            'description' => 'Test incident description',
            'status' => EmployeeIncident::STATUS_OPEN,
            'created_by' => $admin->id,
        ]);

        $template = MemoTemplate::query()->create([
            'company_id' => null,
            'name' => 'Notice to Explain (NTE)',
            'slug' => 'notice-to-explain',
            'description' => 'Test template',
            'body_html' => '<p>Employee: {{employee_name}}</p><p>Incident: {{incident_description}}</p>',
            'is_active' => true,
            'is_system' => true,
            'created_by_user_id' => $admin->id,
        ]);

        $this->mock(MemoPdfService::class, function ($mock) {
            $mock->shouldReceive('renderAndStore')
                ->once()
                ->andReturnUsing(function (int $employeeId, string $title, string $bodyHtml) {
                    $path = 'private/memos/'.$employeeId.'/'.Str::uuid().'.pdf';
                    Storage::disk('local')->put($path, '%PDF-1.4 dummy');

                    return [
                        'pdf_path' => $path,
                        'filename' => 'memo.pdf',
                    ];
                });
        });

        $payload = [
            'memo_template_id' => $template->id,
            'memo_date' => '2026-02-25',
            'hr_signatory_name' => 'HR Manager',
        ];

        $preview = $this->postJson(
            route('employees.incidents.memos.preview', [$employee->employee_id, $incident->id]),
            $payload
        );

        $preview
            ->assertStatus(200)
            ->assertJsonFragment([
                'rendered_html' => '<p>Employee: Jane Doe</p><p>Incident: Test incident description</p>',
            ]);

        $response = $this->post(
            route('employees.incidents.memos.store', [$employee->employee_id, $incident->id]),
            $payload
        );

        $response
            ->assertRedirect(route('employees.show', $employee->employee_id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('memos', [
            'employee_id' => (int) $employee->employee_id,
            'incident_id' => (int) $incident->id,
            'memo_template_id' => (int) $template->id,
            'status' => 'generated',
            'created_by_user_id' => (int) $admin->id,
        ]);

        $this->assertDatabaseCount('memos', 1);
    }

    public function testEmployeeCannotGenerateMemo(): void
    {
        $employeeUser = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->actingAs($employeeUser);

        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $incident = EmployeeIncident::query()->create([
            'employee_id' => (int) $employee->employee_id,
            'category' => 'Conduct',
            'incident_date' => '2026-02-24',
            'description' => 'Test incident description',
            'status' => EmployeeIncident::STATUS_OPEN,
            'created_by' => $employeeUser->id,
        ]);

        $template = MemoTemplate::query()->create([
            'company_id' => null,
            'name' => 'NTE',
            'slug' => 'nte',
            'body_html' => '<p>Employee: {{employee_name}}</p>',
            'is_active' => true,
            'is_system' => true,
        ]);

        $payload = [
            'memo_template_id' => $template->id,
        ];

        $preview = $this->postJson(
            route('employees.incidents.memos.preview', [$employee->employee_id, $incident->id]),
            $payload
        );

        $preview->assertStatus(403);

        $store = $this->post(
            route('employees.incidents.memos.store', [$employee->employee_id, $incident->id]),
            $payload
        );

        $store->assertStatus(403);
    }

    public function testAuthorizedUserCanDownloadMemoPdf(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);

        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $path = 'private/memos/'.$employee->employee_id.'/test.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 dummy');

        $memo = Memo::query()->create([
            'company_id' => null,
            'employee_id' => (int) $employee->employee_id,
            'incident_id' => null,
            'memo_template_id' => null,
            'title' => 'Test Memo',
            'body_rendered_html' => '<p>Test</p>',
            'pdf_path' => $path,
            'status' => 'generated',
            'created_by_user_id' => (int) $admin->id,
        ]);

        $resp = $this->get(route('memos.download', $memo->id));

        $resp
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function testEmployeeCannotDownloadMemoPdf(): void
    {
        Storage::fake('local');

        $employeeUser = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->actingAs($employeeUser);

        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG_'.Str::upper(Str::random(5)),
        ]);

        $employee = Employee::query()->create([
            'department_id' => $department->department_id,
            'employee_code' => 'EMP_'.Str::upper(Str::random(8)),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe+'.Str::lower(Str::random(6)).'@example.com',
            'status' => 'Active',
            'employment_type' => 'Full-Time',
        ]);

        $path = 'private/memos/'.$employee->employee_id.'/test.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 dummy');

        $memo = Memo::query()->create([
            'company_id' => null,
            'employee_id' => (int) $employee->employee_id,
            'incident_id' => null,
            'memo_template_id' => null,
            'title' => 'Test Memo',
            'body_rendered_html' => '<p>Test</p>',
            'pdf_path' => $path,
            'status' => 'generated',
            'created_by_user_id' => (int) $employeeUser->id,
        ]);

        $resp = $this->get(route('memos.download', $memo->id));
        $resp->assertStatus(403);
    }
}
