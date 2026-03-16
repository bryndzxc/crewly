<?php

namespace App\Services;

use App\Models\PagibigContributionSetting;
use App\Models\PhilhealthContributionSetting;
use App\Models\SssContributionTable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GovernmentDefaultsService
{
    /**
     * @return array{inserted:int, skipped:int}
     */
    public function loadPhilhealth2025(): array
    {
        if (!Schema::hasTable('philhealth_contribution_settings')) {
            return ['inserted' => 0, 'skipped' => 0];
        }

        $data = (array) config('government_rates.philhealth_2025', []);
        $effectiveFrom = (string) ($data['effective_from'] ?? '2025-01-01');

        return DB::transaction(function () use ($data, $effectiveFrom) {
            $exists = PhilhealthContributionSetting::query()
                ->whereDate('effective_from', '=', $effectiveFrom)
                ->whereNull('effective_to')
                ->exists();

            if ($exists) {
                return ['inserted' => 0, 'skipped' => 1];
            }

            PhilhealthContributionSetting::query()->create([
                'effective_from' => $effectiveFrom,
                'effective_to' => $data['effective_to'] ?? null,
                'premium_rate' => $data['premium_rate'] ?? null,
                'salary_floor' => $data['salary_floor'] ?? null,
                'salary_ceiling' => $data['salary_ceiling'] ?? null,
                'employee_share_percent' => $data['employee_share_percent'] ?? null,
                'employer_share_percent' => $data['employer_share_percent'] ?? null,
                'notes' => $data['source_notes'] ?? ($data['notes'] ?? null),
                'source_label' => $data['source_label'] ?? null,
                'source_reference_url' => $data['source_reference_url'] ?? null,
                'source_notes' => $data['source_notes'] ?? null,
            ]);

            return ['inserted' => 1, 'skipped' => 0];
        });
    }

    /**
     * @return array{inserted:int, skipped:int}
     */
    public function loadPagibig2025(): array
    {
        if (!Schema::hasTable('pagibig_contribution_settings')) {
            return ['inserted' => 0, 'skipped' => 0];
        }

        $data = (array) config('government_rates.pagibig_2025', []);
        $effectiveFrom = (string) ($data['effective_from'] ?? '2025-01-01');

        return DB::transaction(function () use ($data, $effectiveFrom) {
            $exists = PagibigContributionSetting::query()
                ->whereDate('effective_from', '=', $effectiveFrom)
                ->whereNull('effective_to')
                ->exists();

            if ($exists) {
                return ['inserted' => 0, 'skipped' => 1];
            }

            PagibigContributionSetting::query()->create([
                'effective_from' => $effectiveFrom,
                'effective_to' => $data['effective_to'] ?? null,
                'employee_rate_below_threshold' => $data['employee_rate_below_threshold'] ?? null,
                'employee_rate_above_threshold' => $data['employee_rate_above_threshold'] ?? null,
                'employer_rate' => $data['employer_rate'] ?? null,
                'salary_threshold' => $data['salary_threshold'] ?? null,
                'monthly_cap' => $data['monthly_cap'] ?? null,
                'notes' => $data['source_notes'] ?? ($data['notes'] ?? null),
                'source_label' => $data['source_label'] ?? null,
                'source_reference_url' => $data['source_reference_url'] ?? null,
                'source_notes' => $data['source_notes'] ?? null,
            ]);

            return ['inserted' => 1, 'skipped' => 0];
        });
    }

    /**
     * @return array{inserted:int, skipped:int}
     */
    public function loadSss2025(): array
    {
        if (!Schema::hasTable('sss_contribution_tables')) {
            return ['inserted' => 0, 'skipped' => 0];
        }

        $rows = (array) config('government_rates.sss_2025', []);
        $meta = (array) config('government_rates.meta.sss_2025', []);
        $effectiveFrom = (string) ($meta['effective_from'] ?? '2025-01-01');

        $sourceLabel = (string) ($meta['source_label'] ?? 'SSS Circular No. 2024-006');
        $sourceRefUrl = $meta['source_reference_url'] ?? null;
        $sourceNotes = (string) ($meta['source_notes'] ?? 'Schedule of SSS Contributions Effective January 2025');

        return DB::transaction(function () use ($rows, $effectiveFrom, $sourceLabel, $sourceRefUrl, $sourceNotes) {
            $inserted = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $key = [
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'range_from' => Arr::get($row, 'range_from'),
                    'range_to' => Arr::get($row, 'range_to'),
                ];

                $exists = SssContributionTable::query()
                    ->whereDate('effective_from', '=', $effectiveFrom)
                    ->whereNull('effective_to')
                    ->where('range_from', '=', $key['range_from'])
                    ->where('range_to', '=', $key['range_to'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                SssContributionTable::query()->create([
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'range_from' => $key['range_from'],
                    'range_to' => $key['range_to'],
                    'monthly_salary_credit' => Arr::get($row, 'monthly_salary_credit'),
                    'employee_share' => Arr::get($row, 'employee_share'),
                    'employer_share' => Arr::get($row, 'employer_share'),
                    'ec_share' => Arr::get($row, 'ec_share'),
                    'notes' => $sourceNotes,
                    'source_label' => $sourceLabel,
                    'source_reference_url' => $sourceRefUrl,
                    'source_notes' => $sourceNotes,
                ]);

                $inserted++;
            }

            return ['inserted' => $inserted, 'skipped' => $skipped];
        });
    }
}
