<?php

namespace App\Console\Commands;

use App\Services\GovernmentContributionMonitorService;
use Illuminate\Console\Command;

class GovernmentCheckContributionsCommand extends Command
{
    protected $signature = 'government:check-contributions
        {--source= : Optional: sss|philhealth|pagibig to check only one source}';

    protected $description = 'Check configured government contribution sources and create draft updates when changes are detected.';

    public function handle(GovernmentContributionMonitorService $service): int
    {
        $source = strtolower(trim((string) $this->option('source')));

        if ($source === 'sss') {
            $result = $service->checkSSS();
        } elseif ($source === 'philhealth') {
            $result = $service->checkPhilHealth();
        } elseif ($source === 'pagibig') {
            $result = $service->checkPagibig();
        } else {
            $result = $service->checkAll();
        }

        $this->info('Government contribution check complete.');
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
