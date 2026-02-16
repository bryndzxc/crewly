<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CrewlyEmployeePortalUserCommand extends Command
{
    protected $signature = 'crewly:employee-portal-user';

    protected $description = 'Create/link a portal User (role=employee) to an Employee record (employees.user_id).';

    public function handle(): int
    {
        $employees = Employee::query()
            ->orderBy('employee_code')
            ->limit(500)
            ->get(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix', 'user_id']);

        if ($employees->count() === 0) {
            $this->components->error('No employees found.');
            return self::FAILURE;
        }

        $options = [];
        foreach ($employees as $e) {
            $name = collect([$e->first_name, $e->middle_name, $e->last_name, $e->suffix])
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->implode(' ');

            $tag = $e->user_id ? ' (linked)' : '';
            $options[(string) $e->employee_id] = sprintf('%s — %s%s', (string) $e->employee_code, $name !== '' ? $name : 'Employee', $tag);
        }

        $employeeId = (int) \Laravel\Prompts\select(
            label: 'Select employee (showing first 500) ',
            options: $options,
        );

        /** @var Employee $employee */
        $employee = Employee::query()->findOrFail($employeeId);

        if ($employee->user_id) {
            $ok = \Laravel\Prompts\confirm(
                label: 'This employee already has a linked user. Replace link?',
                default: false
            );

            if (!$ok) {
                $this->components->info('No changes made.');
                return self::SUCCESS;
            }
        }

        $mode = \Laravel\Prompts\select(
            label: 'How do you want to link the portal login?',
            options: [
                'existing' => 'Link an existing user',
                'create' => 'Create a new user (role=Employee) and link it',
            ],
            default: 'create'
        );

        $user = null;

        if ($mode === 'existing') {
            $email = \Laravel\Prompts\text(label: 'User email to link', required: true);

            $user = User::query()->where('email', $email)->first();
            if (!$user) {
                $this->components->error("No user found for email '{$email}'.");
                return self::FAILURE;
            }

            if (!$user->hasRole(User::ROLE_EMPLOYEE)) {
                $ok = \Laravel\Prompts\confirm(label: "User role is '{$user->role()}'. Set to Employee?", default: true);
                if ($ok) {
                    $user->forceFill(['role' => User::ROLE_EMPLOYEE])->save();
                }
            }
        } else {
            $defaultName = collect([$employee->first_name, $employee->last_name])
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->implode(' ');

            $defaultEmail = (string) ($employee->email ?? '');

            $name = \Laravel\Prompts\text(label: 'User name', default: $defaultName !== '' ? $defaultName : null, required: true);
            $email = \Laravel\Prompts\text(label: 'User email', default: $defaultEmail !== '' ? $defaultEmail : null, required: true);

            if (User::query()->where('email', $email)->exists()) {
                $this->components->error("A user with email '{$email}' already exists. Use 'Link an existing user' instead.");
                return self::FAILURE;
            }

            $plainPassword = Str::password(14);

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'role' => User::ROLE_EMPLOYEE,
                'password' => Hash::make($plainPassword),
            ]);

            $this->components->info('User created.');
            $this->line('Temporary password (share securely): ' . $plainPassword);
        }

        $employee->forceFill(['user_id' => (int) $user->id])->save();

        $this->components->info('Employee linked successfully.');
        $this->line('Employee ID: ' . (int) $employee->employee_id);
        $this->line('User ID: ' . (int) $user->id);
        $this->line('User email: ' . (string) $user->email);

        return self::SUCCESS;
    }
}
