<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingChecklistTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string,mixed>
     */
    private function inertiaPropsFromDashboardResponse($response): array
    {
        $html = (string) $response->getContent();

        // Inertia embeds a JSON payload in a `data-page` attribute.
        if (!preg_match('/data-page="([^"]+)"/m', $html, $m)) {
            $this->fail('Unable to find Inertia data-page payload in response.');
        }

        $json = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        $page = json_decode($json, true);

        $this->assertIsArray($page);
        $this->assertArrayHasKey('props', $page);

        return (array) $page['props'];
    }

    public function testDashboardShowsChecklistForHrWithAllItemsIncompleteInitially(): void
    {
        Cache::flush();

        $company = Company::query()->create([
            'name' => 'Test Co',
            'slug' => 'test-co',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
            'attendance_schedule_start' => null,
            'attendance_schedule_end' => null,
        ]);

        $user = User::factory()->create([
            'company_id' => (int) $company->id,
            'role' => User::ROLE_HR,
        ]);

        $resp = $this->actingAs($user)->get(route('dashboard'));
        $resp->assertOk();

        $props = $this->inertiaPropsFromDashboardResponse($resp);
        $this->assertArrayHasKey('onboarding_checklist', $props);

        $checklist = $props['onboarding_checklist'];
        $this->assertIsArray($checklist);
        $this->assertSame(5, (int) ($checklist['total'] ?? 0));
        $this->assertSame(0, (int) ($checklist['completed'] ?? 0));

        $items = $checklist['items'] ?? [];
        $this->assertCount(5, $items);

        $byKey = collect($items)->keyBy('key');

        $this->assertFalse((bool) ($byKey->get('employees_5')['completed'] ?? true));
        $this->assertFalse((bool) ($byKey->get('departments_1')['completed'] ?? true));
        $this->assertFalse((bool) ($byKey->get('leave_types_1')['completed'] ?? true));
    }

    public function testChecklistBecomesCompleteWhenRequirementsMet(): void
    {
        Cache::flush();

        $company = Company::query()->create([
            'name' => 'Ready Co',
            'slug' => 'ready-co',
            'timezone' => (string) config('app.timezone', 'Asia/Manila'),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => (int) $company->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $dept = Department::query()->create([
            'company_id' => (int) $company->id,
            'name' => 'HR',
            'code' => 'HR'.Str::upper(Str::random(3)),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $employeePayload = [
                'company_id' => (int) $company->id,
                'department_id' => (int) $dept->department_id,
                'employee_code' => 'EMP_'.Str::upper(Str::random(6)),
                'first_name' => 'First'.$i,
                'last_name' => 'Last'.$i,
                'email' => "user{$i}@example.test",
                'status' => 'Active',
                'employment_type' => 'Full-Time',
            ];

            if (Schema::hasColumn('employees', 'created_by')) {
                $employeePayload['created_by'] = (int) $user->id;
            }

            Employee::query()->create($employeePayload);
        }

        $leaveTypePayload = [
            'company_id' => (int) $company->id,
            'code' => 'VL',
            'name' => 'Vacation Leave',
            'requires_approval' => true,
            'paid' => true,
            'allow_half_day' => true,
            'default_annual_credits' => 15,
            'is_active' => true,
        ];

        if (Schema::hasColumn('leave_types', 'created_by')) {
            $leaveTypePayload['created_by'] = (int) $user->id;
        }

        LeaveType::query()->create($leaveTypePayload);

        $firstEmployeeId = (int) Employee::query()->value('employee_id');
        $attendancePayload = [
            'company_id' => (int) $company->id,
            'employee_id' => $firstEmployeeId,
            'date' => now()->toDateString(),
            'status' => AttendanceRecord::STATUS_PRESENT,
        ];

        if (Schema::hasColumn('attendance_records', 'created_by')) {
            $attendancePayload['created_by'] = (int) $user->id;
        }

        AttendanceRecord::query()->create($attendancePayload);

        MemoTemplate::query()->create([
            'company_id' => (int) $company->id,
            'name' => 'NTE',
            'slug' => 'nte',
            'description' => 'Test template',
            'body_html' => '<p>Body</p>',
            'is_active' => true,
            'is_system' => false,
            'created_by_user_id' => (int) $user->id,
        ]);

        // Clear cached checklist now that data exists.
        Cache::flush();

        $resp = $this->actingAs($user)->get(route('dashboard'));
        $resp->assertOk();

        $props = $this->inertiaPropsFromDashboardResponse($resp);
        $checklist = (array) ($props['onboarding_checklist'] ?? []);

        $this->assertSame(5, (int) ($checklist['total'] ?? 0));
        $this->assertSame(5, (int) ($checklist['completed'] ?? 0));

        foreach (($checklist['items'] ?? []) as $item) {
            $this->assertTrue((bool) ($item['completed'] ?? false));
        }
    }
}
