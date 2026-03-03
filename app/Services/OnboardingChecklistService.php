<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OnboardingChecklistService extends Service
{
    /**
     * @return array{total:int,completed:int,items:array<int,array{key:string,title:string,description:string,completed:bool,ctaLabel:string,ctaRouteName:string}>}|null
     */
    public function forUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        // Hide from developer-bypass accounts.
        if (method_exists($user, 'isDeveloper') && $user->isDeveloper()) {
            return null;
        }

        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId <= 0) {
            return null;
        }

        // Visible to Admin/HR/Manager only.
        $role = (string) $user->role();
        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true)) {
            return null;
        }

        $cacheKey = sprintf('onboarding_checklist:v1:company:%d:role:%s', $companyId, $role);

        /** @var array{total:int,completed:int,items:array<int,array{key:string,title:string,description:string,completed:bool,ctaLabel:string,ctaRouteName:string}>} $payload */
        $payload = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($companyId) {
            // Only count records created by real users for this company.
            $companyUserIds = User::query()
                ->where('company_id', $companyId)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->all();

            $employeesCount = Employee::query()
                ->when(
                    Schema::hasColumn('employees', 'created_by') && count($companyUserIds) > 0,
                    fn ($q) => $q->whereIn('created_by', $companyUserIds)
                )
                ->count();

            // Departments table does not track created_by in this schema.
            // To avoid seeded/demo data auto-completing this, treat it as complete only once
            // there exists at least one department with a user-created employee assigned.
            $departmentsCount = 0;
            if ($employeesCount > 0) {
                $departmentsCount = (int) Employee::query()
                    ->when(
                        Schema::hasColumn('employees', 'created_by') && count($companyUserIds) > 0,
                        fn ($q) => $q->whereIn('created_by', $companyUserIds)
                    )
                    ->whereNotNull('department_id')
                    ->distinct('department_id')
                    ->count('department_id');
            }

            $leaveTypesCount = LeaveType::query()
                ->when(
                    Schema::hasColumn('leave_types', 'created_by') && count($companyUserIds) > 0,
                    fn ($q) => $q->whereIn('created_by', $companyUserIds)
                )
                ->count();

            $attendanceRecordsCount = AttendanceRecord::query()
                ->when(
                    Schema::hasColumn('attendance_records', 'created_by') && count($companyUserIds) > 0,
                    fn ($q) => $q->whereIn('created_by', $companyUserIds)
                )
                ->count();

            $memoTemplatesCount = MemoTemplate::query()
                ->when(
                    Schema::hasColumn('memo_templates', 'created_by_user_id') && count($companyUserIds) > 0,
                    fn ($q) => $q->whereIn('created_by_user_id', $companyUserIds)
                )
                ->when(
                    Schema::hasColumn('memo_templates', 'is_system'),
                    fn ($q) => $q->where('is_system', false)
                )
                ->count();

            $company = Company::query()
                ->whereKey($companyId)
                ->first([
                    'id',
                    'attendance_schedule_start',
                    'attendance_schedule_end',
                ]);

            $attendanceSettingsConfigured = false;
            if ($company) {
                $start = trim((string) ($company->attendance_schedule_start ?? ''));
                $end = trim((string) ($company->attendance_schedule_end ?? ''));
                // Avoid seeded/demo configs counting as "done". Treat as configured if changed after creation.
                $attendanceSettingsConfigured = $start !== '' && $end !== '' && $company->updated_at && $company->created_at
                    ? $company->updated_at->gt($company->created_at)
                    : false;
            }

            $items = [
                [
                    'key' => 'employees_5',
                    'title' => 'Add at least 5 employees',
                    'description' => 'Build your employee directory so modules can start working.',
                    'completed' => $employeesCount >= 5,
                    'ctaLabel' => 'Go to Employees',
                    'ctaRouteName' => 'employees.index',
                ],
                [
                    'key' => 'departments_1',
                    'title' => 'Create at least 1 department',
                    'description' => 'Organize employees by teams/units (used across reporting and HR flows).',
                    'completed' => $departmentsCount >= 1,
                    'ctaLabel' => 'Go to Departments',
                    'ctaRouteName' => 'departments.index',
                ],
                [
                    'key' => 'leave_types_1',
                    'title' => 'Configure leave types/policies',
                    'description' => 'Define leave categories like VL/SL so leave requests can be filed and approved.',
                    'completed' => $leaveTypesCount >= 1,
                    'ctaLabel' => 'Go to Leave Types',
                    'ctaRouteName' => 'leave.types.index',
                ],
                [
                    'key' => 'attendance_1',
                    'title' => 'Set up attendance',
                    'description' => 'Record at least one entry or configure attendance schedule rules.',
                    'completed' => ($attendanceRecordsCount >= 1) || $attendanceSettingsConfigured,
                    'ctaLabel' => 'Go to Attendance',
                    'ctaRouteName' => 'attendance.daily',
                ],
                [
                    'key' => 'memo_templates_1',
                    'title' => 'Create at least 1 memo template',
                    'description' => 'Templates power fast, consistent HR memo generation.',
                    'completed' => $memoTemplatesCount >= 1,
                    'ctaLabel' => 'Go to Memo Templates',
                    'ctaRouteName' => 'settings.memo_templates.index',
                ],
            ];

            $total = count($items);
            $completed = count(array_filter($items, fn ($i) => (bool) ($i['completed'] ?? false)));

            return [
                'total' => $total,
                'completed' => $completed,
                'items' => array_values($items),
            ];
        });

        return $payload;
    }
}
