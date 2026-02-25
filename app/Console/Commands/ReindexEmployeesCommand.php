<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

class ReindexEmployeesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:reindex-names {--all : Reindex all employees (not just missing indexes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill employee name search indexes (first_name/last_name BI + prefix BI).';

    public function handle(): int
    {
        $all = (bool) $this->option('all');

        $query = Employee::query()->orderBy('employee_id');
        if (! $all) {
            $query->where(function ($q) {
                $q->whereNull('first_name_prefix_bi')
                    ->orWhereJsonLength('first_name_prefix_bi', 0)
                    ->orWhereNull('last_name_prefix_bi')
                    ->orWhereJsonLength('last_name_prefix_bi', 0)
                    ->orWhereNull('first_name_bi')
                    ->orWhereNull('last_name_bi');
            });
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to reindex.');
            return self::SUCCESS;
        }

        $this->info("Reindexing {$total} employee(s)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Employee::withoutTimestamps(function () use ($query, $bar) {
            $query->chunkById(200, function ($employees) use ($bar) {
                foreach ($employees as $employee) {
                    // Triggers the Employee::saving hook which computes the indexes.
                    $employee->save();
                    $bar->advance();
                }
            }, 'employee_id');
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
