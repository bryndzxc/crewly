<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;

class PlanLimitService extends Service
{
    private const COUNTED_EMPLOYEE_STATUSES = ['Active', 'On Leave'];

    public function canCreateEmployee(int $companyId): bool
    {
        $usage = $this->employeeUsage($companyId);

        if (($usage['max'] ?? 0) <= 0) {
            return true;
        }

        return (int) ($usage['used'] ?? 0) < (int) ($usage['max'] ?? 0);
    }

    /**
     * @return array{used:int,max:int,remaining:int,near_limit:bool,at_limit:bool}
     */
    public function employeeUsage(int $companyId): array
    {
        $max = (int) (Company::query()->whereKey($companyId)->value('max_employees') ?? 0);

        $used = Employee::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereIn('status', self::COUNTED_EMPLOYEE_STATUSES)
            ->count();

        $remaining = $max > 0 ? max(0, $max - $used) : 0;
        $atLimit = $max > 0 && $used >= $max;
        $nearLimit = $max > 0 && !$atLimit && $used >= max(0, $max - 2);

        return [
            'used' => (int) $used,
            'max' => (int) $max,
            'remaining' => (int) $remaining,
            'near_limit' => (bool) $nearLimit,
            'at_limit' => (bool) $atLimit,
        ];
    }
}
