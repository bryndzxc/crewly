<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedGovernmentContributionsCommand extends Command
{
    protected $signature = 'crewly:seed-government-contributions {--force : Overwrite existing government contribution settings (DANGEROUS)}';

    protected $description = 'Seed starter PH government contributions config (PhilHealth + Pag-IBIG) and optionally SSS from CSV.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        if ($force) {
            $this->warn('FORCE mode enabled: existing contribution settings may be deleted.');
        }

        // PhilHealth
        if (Schema::hasTable('philhealth_contribution_settings')) {
            if ($force) {
                DB::table('philhealth_contribution_settings')->truncate();
            }
            $this->callSilent('db:seed', [
                '--class' => \Database\Seeders\PhilhealthContributionSettingSeeder::class,
                '--force' => true,
            ]);
            $this->info('PhilHealth settings ensured.');
        } else {
            $this->warn("Skipped PhilHealth: missing table 'philhealth_contribution_settings'. Run migrations first.");
        }

        // Pag-IBIG
        if (Schema::hasTable('pagibig_contribution_settings')) {
            if ($force) {
                DB::table('pagibig_contribution_settings')->truncate();
            }
            $this->callSilent('db:seed', [
                '--class' => \Database\Seeders\PagibigContributionSettingSeeder::class,
                '--force' => true,
            ]);
            $this->info('Pag-IBIG settings ensured.');
        } else {
            $this->warn("Skipped Pag-IBIG: missing table 'pagibig_contribution_settings'. Run migrations first.");
        }

        // SSS
        if (Schema::hasTable('sss_contribution_tables')) {
            if ($force) {
                DB::table('sss_contribution_tables')->truncate();
            }

            $csvPath = base_path('database/seed-data/sss_contribution_table.csv');
            if (!is_file($csvPath)) {
                $this->warn('SSS rules not seeded: missing CSV at database/seed-data/sss_contribution_table.csv');
                $this->line('Use the template at database/seed-data/sss_contribution_table.template.csv, copy it to sss_contribution_table.csv, then rerun this command.');
            } else {
                $this->callSilent('db:seed', [
                    '--class' => \Database\Seeders\SssContributionTableSeeder::class,
                    '--force' => true,
                ]);

                $count = (int) DB::table('sss_contribution_tables')->count();
                if ($count > 0) {
                    $this->info("SSS rules seeded: {$count} rows.");
                } else {
                    $this->warn('SSS CSV was found, but 0 rules were seeded. Check that the CSV has rows (not just a header) and that numeric fields are plain numbers (no commas).');
                    $this->line('Expected columns: effective_from,effective_to,range_from,range_to,monthly_salary_credit,employee_share,employer_share,ec_share,notes');
                }
            }
        } else {
            $this->warn("Skipped SSS: missing table 'sss_contribution_tables'. Run migrations first.");
        }

        $this->newLine();
        $this->info('Done. Review settings under HR Settings → Government Contributions.');

        return self::SUCCESS;
    }
}
