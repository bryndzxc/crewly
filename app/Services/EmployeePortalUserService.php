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
        $result = $this->ensureLinkedWithPassword($employee);
        return $result['user'];
    }

    /**
     * @return array{user:?User,created_new:bool,password_plain:?string}
     */
    public function ensureLinkedWithPassword(Employee $employee): array
    {
        if ($employee->user_id) {
            /** @var ?User $existing */
            $existing = $employee->user()->first();
            return ['user' => $existing, 'created_new' => false, 'password_plain' => null];
        }

        $companyId = (int) ($employee->company_id ?? 0);
        if ($companyId < 1) {
            throw new \RuntimeException('Employee company_id is missing; cannot create portal user.');
        }

        $email = strtolower(trim((string) ($employee->email ?? '')));
        if ($email === '') {
            return ['user' => null, 'created_new' => false, 'password_plain' => null];
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            if ((int) ($user->company_id ?? 0) !== $companyId) {
                throw new \RuntimeException("User '{$email}' belongs to another company.");
            }

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

            $employee->forceFill(['user_id' => (int) $user->id])->save();

            return ['user' => $user, 'created_new' => false, 'password_plain' => null];
        }

        $name = $this->employeeDisplayName($employee);

        $configured = config('crewly.employee_portal.default_password');
        $plainPassword = is_string($configured) && trim($configured) !== ''
            ? (string) $configured
            : Str::password(14);

        /** @var User $user */
        $user = User::query()->create([
            'company_id' => $companyId,
            'name' => $name,
            'email' => $email,
            'role' => User::ROLE_EMPLOYEE,
            'password' => Hash::make($plainPassword),
            'must_change_password' => true,
        ]);

        $employee->forceFill(['user_id' => (int) $user->id])->save();

        return ['user' => $user, 'created_new' => true, 'password_plain' => $plainPassword];
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
