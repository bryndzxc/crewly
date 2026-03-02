<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\DemoCompanyCleanupService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupDemoCompaniesCommand extends Command
{
    protected $signature = 'demo:cleanup
        {--max-days= : Expire demo companies older than this many days (default: config crewly.demo.max_days)}
        {--purge : Also delete demo company data (employees, documents, memos, etc.)}
        {--force : Skip confirmation prompts (required for purge)}
        {--dry-run : Print what would change without saving}';

    protected $description = 'Expire demo companies by disabling them after a maximum age.';

    public function handle(): int
    {
        $maxDays = $this->resolveMaxDays();
        if ($maxDays < 1) {
            $this->error('Invalid --max-days. Must be >= 1.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $purge = (bool) $this->option('purge');
        $force = (bool) $this->option('force');

        if ($purge && ! $force) {
            $this->error('Refusing to purge without --force. Run with --dry-run first, then add --force when ready.');
            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($maxDays);

        $companiesQuery = Company::query()
            ->select(['id', 'name', 'slug', 'is_active', 'created_at'])
            ->where('is_demo', true)
            ->where('created_at', '<=', $cutoff)
            ->orderBy('created_at');

        // If we're only expiring (disable), process only currently-active demo companies.
        // If purging, allow cleanup even if they were previously expired/disabled.
        if (! $purge) {
            $companiesQuery->where('is_active', true);
        }

        $companies = $companiesQuery->get();

        if ($companies->isEmpty()) {
            $this->info("No demo companies to expire (max-days={$maxDays}).");
            return self::SUCCESS;
        }

        $this->info(
            "Expiring {$companies->count()} demo compan" . ($companies->count() === 1 ? 'y' : 'ies')
            . " (max-days={$maxDays})"
            . ($purge ? ' [PURGE]' : '')
            . ($dryRun ? ' [DRY RUN]' : '')
            . '...'
        );

        $expired = 0;
        foreach ($companies as $company) {
            $label = "#{$company->id} {$company->name} ({$company->slug})";
            $createdAt = $company->created_at ? $company->created_at->toDateTimeString() : null;
            $this->line("- Expire {$label} (created_at={$createdAt})");

            if ($dryRun) {
                if ($purge) {
                    $this->printPurgeSummary((int) $company->id, (string) $company->slug);
                }
                continue;
            }

            if ($purge) {
                if (! $force) {
                    $this->error('Internal safety: purge requires --force.');
                    return self::FAILURE;
                }

                DB::transaction(function () use ($company) {
                    app(DemoCompanyCleanupService::class)->purgeCompanyData(
                        (int) $company->id,
                        (string) $company->slug,
                        ['delete_users' => true, 'delete_leads' => true]
                    );
                });
            }

            $company->forceFill(['is_active' => false])->save();
            $expired++;

            Log::info('Expired demo company.', [
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                'max_days' => $maxDays,
            ]);
        }

        if (! $dryRun) {
            $this->info("Expired {$expired} demo compan" . ($expired === 1 ? 'y' : 'ies') . '.');
        }

        return self::SUCCESS;
    }

    private function printPurgeSummary(int $companyId, string $companySlug): void
    {
        $employeeCount = (int) DB::table('employees')->where('company_id', $companyId)->count();
        $userCount = (int) DB::table('users')->where('company_id', $companyId)->count();
        $memoCount = (int) DB::table('memos')->where('company_id', $companyId)->count();
        $employeeDocs = (int) DB::table('employee_documents')->where('company_id', $companyId)->count();
        $applicantCount = (int) DB::table('applicants')->where('company_id', $companyId)->count();
        $applicantDocs = (int) DB::table('applicant_documents')->where('company_id', $companyId)->count();

        $this->line("  Purge would delete:");
        $this->line("  - users: {$userCount}");
        $this->line("  - employees: {$employeeCount}");
        $this->line("  - employee_documents: {$employeeDocs}");
        $this->line("  - applicants: {$applicantCount}");
        $this->line("  - applicant_documents: {$applicantDocs}");
        $this->line("  - memos: {$memoCount}");
        $this->line("  Purge would also remove stored files for this company (docs/photos/memo PDFs) and local seed PDFs under memos/seed/{$companySlug}/.");
    }

    private function resolveMaxDays(): int
    {
        $value = $this->option('max-days');
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) config('crewly.demo.max_days', 7);
    }
}
