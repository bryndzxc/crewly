<?php

namespace Database\Seeders;

use App\Models\PagibigContributionSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PagibigContributionSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('pagibig_contribution_settings')) {
            return;
        }

        // Idempotent: do not overwrite existing configuration.
        if (PagibigContributionSetting::query()->count() > 0) {
            return;
        }

        // Starter defaults only — verify against the official Pag-IBIG HDMF rules for your payroll year.
        PagibigContributionSetting::query()->create([
            'effective_from' => Carbon::create(2026, 1, 1)->toDateString(),
            'effective_to' => null,

            // Common starter assumptions (verify):
            // - Employee share: 1% up to threshold, else 2%
            // - Employer share: 2%
            // - Cap applied to the computed share amount
            'salary_threshold' => 1500.00,
            'employee_rate_below_threshold' => 0.0100,
            'employee_rate_above_threshold' => 0.0200,
            'employer_rate' => 0.0200,
            'monthly_cap' => 100.00,

            'notes' => 'Seeded starter defaults. Verify thresholds/rates/caps with Pag-IBIG HDMF for your payroll year.',
        ]);
    }
}
