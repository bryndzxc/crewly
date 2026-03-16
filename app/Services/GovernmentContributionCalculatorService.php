<?php

namespace App\Services;

use App\Exceptions\GovernmentContributionConfigMissingException;
use App\Models\PagibigContributionSetting;
use App\Models\PhilhealthContributionSetting;
use App\Models\SssContributionTable;
use Illuminate\Support\Carbon;

class GovernmentContributionCalculatorService
{
    /**
     * @return array{employee_share: float, employer_share: float, ec_share: float, matched_rule_id: int|null}
     */
    public function calculateSSS(float $monthlySalary, Carbon|string $effectivityDate): array
    {
        $date = $this->normalizeDate($effectivityDate);

        $rule = SssContributionTable::query()
            ->activeOn($date)
            ->matchesSalary($monthlySalary)
            ->orderBy('range_from', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$rule) {
            // If there are no active SSS rules at all for that date, error differently.
            $hasActive = SssContributionTable::query()->activeOn($date)->exists();
            if (!$hasActive) {
                throw GovernmentContributionConfigMissingException::forProvider('SSS', $date->toDateString());
            }

            throw GovernmentContributionConfigMissingException::forSssRange($date->toDateString(), $monthlySalary);
        }

        return [
            'employee_share' => $this->round2((float) ($rule->employee_share ?? 0)),
            'employer_share' => $this->round2((float) ($rule->employer_share ?? 0)),
            'ec_share' => $this->round2((float) ($rule->ec_share ?? 0)),
            'matched_rule_id' => (int) $rule->id,
        ];
    }

    /**
     * @return array{employee_share: float, employer_share: float, ec_share: float, matched_rule_id: int|null}
     */
    public function calculatePhilHealth(float $monthlySalary, Carbon|string $effectivityDate): array
    {
        $date = $this->normalizeDate($effectivityDate);

        $setting = PhilhealthContributionSetting::query()
            ->activeOn($date)
            ->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$setting) {
            throw GovernmentContributionConfigMissingException::forProvider('PhilHealth', $date->toDateString());
        }

        $floor = (float) ($setting->salary_floor ?? 0);
        $ceiling = (float) ($setting->salary_ceiling ?? 0);

        $salary = $monthlySalary;
        if ($salary < $floor) {
            $salary = $floor;
        }
        if ($ceiling > 0 && $salary > $ceiling) {
            $salary = $ceiling;
        }

        $premiumRate = (float) ($setting->premium_rate ?? 0);
        $totalPremium = $salary * $premiumRate;

        $employeePercent = (float) ($setting->employee_share_percent ?? 0);
        $employerPercent = (float) ($setting->employer_share_percent ?? 0);

        return [
            'employee_share' => $this->round2($totalPremium * $employeePercent),
            'employer_share' => $this->round2($totalPremium * $employerPercent),
            'ec_share' => 0.0,
            'matched_rule_id' => (int) $setting->id,
        ];
    }

    /**
     * @return array{employee_share: float, employer_share: float, ec_share: float, matched_rule_id: int|null}
     */
    public function calculatePagibig(float $monthlySalary, Carbon|string $effectivityDate): array
    {
        $date = $this->normalizeDate($effectivityDate);

        $setting = PagibigContributionSetting::query()
            ->activeOn($date)
            ->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$setting) {
            throw GovernmentContributionConfigMissingException::forProvider('Pag-IBIG', $date->toDateString());
        }

        $threshold = $setting->salary_threshold !== null ? (float) $setting->salary_threshold : null;
        $belowRate = $setting->employee_rate_below_threshold !== null ? (float) $setting->employee_rate_below_threshold : null;
        $aboveRate = $setting->employee_rate_above_threshold !== null ? (float) $setting->employee_rate_above_threshold : null;
        $employerRate = $setting->employer_rate !== null ? (float) $setting->employer_rate : null;
        $cap = $setting->monthly_cap !== null ? (float) $setting->monthly_cap : null;

        $employeeRate = 0.0;
        if ($threshold !== null && $monthlySalary > $threshold) {
            $employeeRate = (float) ($aboveRate ?? $belowRate ?? 0);
        } else {
            $employeeRate = (float) ($belowRate ?? $aboveRate ?? 0);
        }

        $employee = $monthlySalary * $employeeRate;
        $employer = $monthlySalary * (float) ($employerRate ?? 0);

        // Cap applies to the share amounts (per-month contribution cap).
        if ($cap !== null && $cap > 0) {
            $employee = min($employee, $cap);
            $employer = min($employer, $cap);
        }

        return [
            'employee_share' => $this->round2($employee),
            'employer_share' => $this->round2($employer),
            'ec_share' => 0.0,
            'matched_rule_id' => (int) $setting->id,
        ];
    }

    private function normalizeDate(Carbon|string $date): Carbon
    {
        return $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse((string) $date)->startOfDay();
    }

    private function round2(float $value): float
    {
        // Small epsilon avoids occasional -0.00 or 1.005 rounding quirks.
        $rounded = round($value + 1e-9, 2);
        if (abs($rounded) < 0.00001) {
            return 0.0;
        }
        return $rounded;
    }
}
