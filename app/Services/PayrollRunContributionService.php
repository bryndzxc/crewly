<?php

namespace App\Services;

use App\Models\PayrollRunContribution;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayrollRunContributionService extends Service
{
    public function __construct(
        private readonly GovernmentContributionCalculatorService $calculator,
    ) {}

    /**
     * Compute and persist government contributions per employee for a payroll period.
     *
     * Override behavior:
     * - Always refresh *_computed fields.
     * - If a provider is NOT overridden, also refresh the effective fields.
     * - If overridden, keep the effective fields as-is.
     *
     * @param array<int, int> $employeeIds
     * @param array<int, float> $baseSalaryByEmployeeId
     * @return Collection<int, PayrollRunContribution> keyed by employee_id
     */
    public function upsertForPeriod(
        array $employeeIds,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $baseSalaryByEmployeeId,
        ?int $actorUserId = null,
    ): Collection {
        $employeeIds = array_values(array_unique(array_map('intval', $employeeIds)));
        if (count($employeeIds) === 0) {
            return collect();
        }

        $from = $periodStart->copy()->startOfDay();
        $to = $periodEnd->copy()->startOfDay();

        /** @var Collection<int, PayrollRunContribution> $existing */
        $existing = PayrollRunContribution::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('payroll_period_start', $from->toDateString())
            ->whereDate('payroll_period_end', $to->toDateString())
            ->get()
            ->keyBy(fn (PayrollRunContribution $r) => (int) $r->employee_id);

        $out = collect();

        foreach ($employeeIds as $employeeId) {
            $baseSalary = (float) ($baseSalaryByEmployeeId[$employeeId] ?? 0);

            $sss = $this->calculator->calculateSSS($baseSalary, $to);
            $philhealth = $this->calculator->calculatePhilHealth($baseSalary, $to);
            $pagibig = $this->calculator->calculatePagibig($baseSalary, $to);

            /** @var PayrollRunContribution|null $row */
            $row = $existing->get($employeeId);

            $sssEmployeeComputed = (float) ($sss['employee_share'] ?? 0);
            $sssEmployerComputed = (float) ($sss['employer_share'] ?? 0);

            $phEmployeeComputed = (float) ($philhealth['employee_share'] ?? 0);
            $phEmployerComputed = (float) ($philhealth['employer_share'] ?? 0);

            $piEmployeeComputed = (float) ($pagibig['employee_share'] ?? 0);
            $piEmployerComputed = (float) ($pagibig['employer_share'] ?? 0);

            $isSssOverridden = (bool) ($row?->sss_overridden ?? false);
            $isPhilhealthOverridden = (bool) ($row?->philhealth_overridden ?? false);
            $isPagibigOverridden = (bool) ($row?->pagibig_overridden ?? false);

            $payload = [
                'base_salary' => $baseSalary,

                'sss_employee_computed' => $sssEmployeeComputed,
                'sss_employer_computed' => $sssEmployerComputed,
                'philhealth_employee_computed' => $phEmployeeComputed,
                'philhealth_employer_computed' => $phEmployerComputed,
                'pagibig_employee_computed' => $piEmployeeComputed,
                'pagibig_employer_computed' => $piEmployerComputed,

                'updated_by' => $actorUserId,
            ];

            if (!$row) {
                $payload += [
                    'employee_id' => $employeeId,
                    'payroll_period_start' => $from->toDateString(),
                    'payroll_period_end' => $to->toDateString(),

                    'sss_employee' => $sssEmployeeComputed,
                    'sss_employer' => $sssEmployerComputed,
                    'philhealth_employee' => $phEmployeeComputed,
                    'philhealth_employer' => $phEmployerComputed,
                    'pagibig_employee' => $piEmployeeComputed,
                    'pagibig_employer' => $piEmployerComputed,

                    'created_by' => $actorUserId,
                ];

                $row = PayrollRunContribution::query()->create($payload);
                $out->put($employeeId, $row);
                continue;
            }

            if (!$isSssOverridden) {
                $payload['sss_employee'] = $sssEmployeeComputed;
                $payload['sss_employer'] = $sssEmployerComputed;
            }
            if (!$isPhilhealthOverridden) {
                $payload['philhealth_employee'] = $phEmployeeComputed;
                $payload['philhealth_employer'] = $phEmployerComputed;
            }
            if (!$isPagibigOverridden) {
                $payload['pagibig_employee'] = $piEmployeeComputed;
                $payload['pagibig_employer'] = $piEmployerComputed;
            }

            $row->forceFill($payload)->save();
            $out->put($employeeId, $row->refresh());
        }

        return $out;
    }
}
