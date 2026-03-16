<?php

namespace Database\Seeders;

use App\Models\SssContributionTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SssContributionTableSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('sss_contribution_tables')) {
            return;
        }

        // Idempotent: do not overwrite existing configuration.
        if (SssContributionTable::query()->count() > 0) {
            return;
        }

        // SSS tables are typically large and change over time.
        // We intentionally do not hardcode an official table here.
        // Provide a CSV at database/seed-data/sss_contribution_table.csv to seed rules.
        $csvPath = base_path('database/seed-data/sss_contribution_table.csv');
        if (!is_file($csvPath)) {
            return;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return;
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return;
        }

        $header = array_map(fn ($v) => trim((string) $v), $header);
        $index = array_flip($header);

        $required = [
            'effective_from',
            'effective_to',
            'range_from',
            'range_to',
            'monthly_salary_credit',
            'employee_share',
            'employer_share',
            'ec_share',
            'notes',
        ];

        foreach ($required as $col) {
            if (!array_key_exists($col, $index)) {
                fclose($handle);
                return;
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row) || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $get = function (string $col) use ($row, $index) {
                $pos = $index[$col] ?? null;
                return $pos === null ? null : ($row[$pos] ?? null);
            };

            $toNull = fn ($v) => (($t = trim((string) ($v ?? ''))) === '' ? null : $t);
            $toFloatOrNull = function ($v) {
                $t = trim((string) ($v ?? ''));
                if ($t === '') {
                    return null;
                }

                // Be tolerant of common formatting from Excel/PDF conversions.
                // Examples: "10,000.00", "\u20b1100.00", " 1,000 ".
                $t = preg_replace('/[\s,\x{20B1}]/u', '', $t) ?? $t;

                return (float) $t;
            };

            $effectiveFrom = $toNull($get('effective_from'));
            $rangeFrom = $toFloatOrNull($get('range_from'));
            $rangeTo = $toFloatOrNull($get('range_to'));
            $employeeShare = $toFloatOrNull($get('employee_share'));
            $employerShare = $toFloatOrNull($get('employer_share'));

            if ($effectiveFrom === null || $rangeFrom === null || $rangeTo === null || $employeeShare === null || $employerShare === null) {
                continue;
            }

            SssContributionTable::query()->create([
                'effective_from' => $effectiveFrom,
                'effective_to' => $toNull($get('effective_to')),
                'range_from' => $rangeFrom,
                'range_to' => $rangeTo,
                'monthly_salary_credit' => $toFloatOrNull($get('monthly_salary_credit')),
                'employee_share' => (float) $employeeShare,
                'employer_share' => (float) $employerShare,
                'ec_share' => $toFloatOrNull($get('ec_share')),
                'notes' => $toNull($get('notes')),
            ]);
        }

        fclose($handle);
    }
}
