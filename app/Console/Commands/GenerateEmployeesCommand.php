<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateEmployeesCommand extends Command
{
    protected $signature = 'demo:generate-employees
        {count=10 : Number of employees to generate}
        {--company-id= : Company ID (overrides --company-slug)}
        {--company-slug= : Company slug (default: crewly.demo.shared.company_slug)}
        {--department-id= : Department ID to assign (optional)}
        {--with-portal-users : Create a portal user (role=employee) for each employee and link employees.user_id}
        {--password= : Password to use for generated portal users (optional; if omitted, a random password will be generated and printed once)}
        {--dry-run : Print what would be created without saving}';

    protected $description = 'Generate a batch of employees for a company (demo utility).';

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        if ($count < 1) {
            $this->components->error('Invalid count. Must be >= 1.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $withUsers = (bool) $this->option('with-portal-users');

        $company = $this->resolveCompany();
        if (!$company) {
            return self::FAILURE;
        }

        $department = $this->resolveDepartment($company);
        if (!$department) {
            return self::FAILURE;
        }

        $existingCount = (int) Employee::withoutCompanyScope()->where('company_id', (int) $company->id)->count();
        $maxEmployees = (int) ($company->max_employees ?? 0);

        if ($maxEmployees > 0 && ($existingCount + $count) > $maxEmployees) {
            $allowed = max(0, $maxEmployees - $existingCount);

            if ($allowed === 0) {
                $this->components->error("Company already has {$existingCount} employees (max_employees={$maxEmployees}). Nothing to generate.");
                return self::FAILURE;
            }

            $this->components->warn("Requested {$count} employees, but company max is {$maxEmployees}. Generating {$allowed} instead.");
            $count = $allowed;
        }

        $plainPassword = null;
        if ($withUsers) {
            $plainPassword = trim((string) $this->option('password'));
            if ($plainPassword === '') {
                $plainPassword = Str::password(14);
            }
        }

        $this->line("Target company: #{$company->id} {$company->name} ({$company->slug})");
        $this->line("Department: #{$department->department_id} {$department->name} ({$department->code})");
        $this->line('Existing employees: ' . $existingCount);
        $this->line('Generating employees: ' . $count . ($dryRun ? ' [DRY RUN]' : ''));

        if ($withUsers) {
            $this->line('Also creating linked portal users: yes');
        }

        $createdEmployees = 0;
        $createdUsers = 0;

        $faker = fake();

        try {
            DB::transaction(function () use ($company, $department, $count, $withUsers, $plainPassword, $dryRun, $faker, &$createdEmployees, &$createdUsers) {
                for ($i = 0; $i < $count; $i++) {
                    $firstName = (string) $faker->firstName();
                    $lastName = (string) $faker->lastName();
                    $middleName = $faker->boolean(30) ? (string) $faker->firstName() : null;

                    $employeeCode = $this->generateUniqueEmployeeCode($company);

                    $baseEmailUser = Str::lower(Str::slug($firstName . '.' . $lastName, '.'));
                    $email = $baseEmailUser . "+{$company->id}.{$employeeCode}@demo.crewly.test";

                    $monthlyRate = (float) $faker->numberBetween(18000, 60000);

                    $payload = [
                        'company_id' => (int) $company->id,
                        'department_id' => (int) $department->department_id,
                        'employee_code' => $employeeCode,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'last_name' => $lastName,
                        'suffix' => null,
                        'email' => $email,
                        'mobile_number' => null,
                        'status' => 'Active',
                        'position_title' => (string) $faker->randomElement([
                            'Driver',
                            'Helper',
                            'Warehouse Staff',
                            'Admin Assistant',
                            'Cashier',
                            'Sales Associate',
                        ]),
                        'date_hired' => now()->subDays((int) $faker->numberBetween(1, 120))->toDateString(),
                        'regularization_date' => now()->addDays((int) $faker->numberBetween(30, 120))->toDateString(),
                        'employment_type' => (string) $faker->randomElement(['Full-Time', 'Part-Time', 'Contractor', 'Intern']),
                        'monthly_rate' => $monthlyRate,
                        'notes' => null,
                        'created_by' => null,
                    ];

                    if ($dryRun) {
                        $this->line("- {$employeeCode} {$firstName} {$lastName} ({$payload['position_title']})");
                        $createdEmployees++;
                        continue;
                    }

                    /** @var Employee $employee */
                    $employee = Employee::withoutCompanyScope()->create($payload);
                    $createdEmployees++;

                    if ($withUsers) {
                        $user = $this->createPortalUserForEmployee($company, $employee, (string) $plainPassword);
                        if ($user) {
                            $createdUsers++;
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->components->error('Failed to generate employees.');
            $this->line($e->getMessage());
            return self::FAILURE;
        }

        $this->components->info("Employees generated: {$createdEmployees}");

        if ($withUsers) {
            $this->components->info("Portal users created/linked: {$createdUsers}");
            if ($plainPassword) {
                $this->line('Generated portal password (share securely): ' . $plainPassword);
            }
        }

        return self::SUCCESS;
    }

    private function resolveCompany(): ?Company
    {
        $companyId = $this->option('company-id');
        if (is_numeric($companyId)) {
            /** @var Company|null $company */
            $company = Company::query()->find((int) $companyId);
            if (!$company) {
                $this->components->error("No company found for ID '{$companyId}'.");
                return null;
            }

            return $company;
        }

        $slug = trim((string) ($this->option('company-slug') ?: config('crewly.demo.shared.company_slug', '')));
        if ($slug === '') {
            $this->components->error('Missing company selector. Provide --company-id or --company-slug (or set crewly.demo.shared.company_slug).');
            return null;
        }

        /** @var Company|null $company */
        $company = Company::query()->where('slug', $slug)->first();
        if (!$company) {
            $this->components->error("No company found for slug '{$slug}'.");
            $this->line("Tip: create it first via 'php artisan demo:ensure-shared'.");
            return null;
        }

        return $company;
    }

    private function resolveDepartment(Company $company): ?Department
    {
        $departmentId = $this->option('department-id');
        if (is_numeric($departmentId)) {
            /** @var Department|null $department */
            $department = Department::withoutCompanyScope()->where('department_id', (int) $departmentId)->first();
            if (!$department) {
                $this->components->error("No department found for ID '{$departmentId}'.");
                return null;
            }

            if ((int) ($department->company_id ?? 0) !== (int) $company->id) {
                $this->components->error('Selected department does not belong to the target company.');
                return null;
            }

            return $department;
        }

        /** @var Department|null $department */
        $department = Department::withoutCompanyScope()
            ->where('company_id', (int) $company->id)
            ->orderBy('department_id')
            ->first();

        if ($department) {
            return $department;
        }

        $base = strtoupper(Str::limit(Str::slug($company->slug ?: 'cmp'), 6, ''));

        for ($i = 0; $i < 20; $i++) {
            $code = $base . '-OPS-' . Str::upper(Str::random(4));

            if (Department::withoutCompanyScope()->where('code', $code)->exists()) {
                continue;
            }

            return Department::withoutCompanyScope()->create([
                'company_id' => (int) $company->id,
                'name' => 'Operations',
                'code' => $code,
            ]);
        }

        $this->components->error('Could not generate a unique department code.');
        return null;
    }

    private function generateUniqueEmployeeCode(Company $company): string
    {
        $base = strtoupper(Str::limit(Str::slug($company->slug ?: 'cmp'), 6, ''));
        $companyId = (int) $company->id;

        for ($i = 0; $i < 30; $i++) {
            $code = $base . '-' . $companyId . '-' . Str::upper(Str::random(6));
            if (!Employee::withoutCompanyScope()->where('employee_code', $code)->exists()) {
                return $code;
            }
        }

        // Extremely unlikely fallback.
        return $base . '-' . $companyId . '-' . Str::upper(Str::random(10));
    }

    private function createPortalUserForEmployee(Company $company, Employee $employee, string $plainPassword): ?User
    {
        $email = (string) ($employee->email ?? '');
        $name = collect([$employee->first_name, $employee->last_name])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->implode(' ');

        if (trim($email) === '') {
            return null;
        }

        // If user already exists for this email, link it (as long as same company).
        $existing = User::query()->where('email', $email)->first();
        if ($existing) {
            if ((int) ($existing->company_id ?? 0) !== (int) $company->id) {
                $this->components->warn("Skipping portal user for {$employee->employee_code}: email already exists on another company.");
                return null;
            }

            if (!$existing->hasRole(User::ROLE_EMPLOYEE)) {
                $existing->forceFill(['role' => User::ROLE_EMPLOYEE])->save();
            }

            $employee->forceFill(['user_id' => (int) $existing->id])->save();
            return $existing;
        }

        $user = User::query()->create([
            'company_id' => (int) $company->id,
            'name' => $name !== '' ? $name : 'Employee',
            'email' => $email,
            'role' => User::ROLE_EMPLOYEE,
            'password' => Hash::make($plainPassword),
            'must_change_password' => false,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $employee->forceFill(['user_id' => (int) $user->id])->save();

        return $user;
    }
}
