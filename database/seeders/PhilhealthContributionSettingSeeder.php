<?php

namespace Database\Seeders;

use App\Models\PhilhealthContributionSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PhilhealthContributionSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('philhealth_contribution_settings')) {
            return;
        }

        // Idempotent: do not overwrite existing configuration.
        if (PhilhealthContributionSetting::query()->count() > 0) {
            return;
        }

        // Starter defaults only — verify against the official PhilHealth circular for your payroll year.
        PhilhealthContributionSetting::query()->create([
            'effective_from' => Carbon::create(2026, 1, 1)->toDateString(),
            'effective_to' => null,

            // Total premium = salary (clamped to floor/ceiling) * premium_rate.
            // Employee/Employer split is handled via *_share_percent.
            'premium_rate' => 0.0500,
            'salary_floor' => 10000.00,
            'salary_ceiling' => 100000.00,
            'employee_share_percent' => 0.5000,
            'employer_share_percent' => 0.5000,

            'notes' => 'Seeded starter defaults. Verify with the official PhilHealth premium rate + floor/ceiling for your payroll year.',
        ]);
    }
}
