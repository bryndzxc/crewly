<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeePortalUserService extends Service
{
    public function ensureLinked(Employee $employee): ?User
    {
        if ($employee->user_id) {
            /** @var ?User $existing */
            $existing = $employee->user()->first();
            return $existing;
        }

        $email = strtolower(trim((string) ($employee->email ?? '')));
        if ($email === '') {
            return null;
        }

        $user = User::query()->where('email', $email)->first();
        $createdNew = false;

        if ($user) {
            $linkedToAnother = Employee::query()
                ->where('user_id', (int) $user->id)
                ->where('employee_id', '!=', (int) $employee->employee_id)
                ->exists();

            if ($linkedToAnother) {
                throw new \RuntimeException("User '{$email}' is already linked to another employee.");
            }

            if (!$user->hasRole(User::ROLE_EMPLOYEE)) {
                $user->forceFill(['role' => User::ROLE_EMPLOYEE])->save();
            }
        } else {
            $name = $this->employeeDisplayName($employee);

            $configured = config('crewly.employee_portal.default_password');
            $plainPassword = is_string($configured) && trim($configured) !== ''
                ? (string) $configured
                : Str::password(14);

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'role' => User::ROLE_EMPLOYEE,
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
            ]);

            $createdNew = true;
        }

        $employee->forceFill(['user_id' => (int) $user->id])->save();

        return $user;
    }

    private function employeeDisplayName(Employee $employee): string
    {
        $parts = [
            trim((string) ($employee->first_name ?? '')),
            trim((string) ($employee->middle_name ?? '')),
            trim((string) ($employee->last_name ?? '')),
            trim((string) ($employee->suffix ?? '')),
        ];

        $name = trim(implode(' ', array_values(array_filter($parts, fn ($p) => $p !== ''))));

        return $name !== '' ? $name : ('Employee #' . (string) $employee->employee_id);
    }
}
