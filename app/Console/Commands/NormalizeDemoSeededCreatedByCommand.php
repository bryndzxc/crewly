<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeDemoSeededCreatedByCommand extends Command
{
    /**
     * Examples:
     * - Null out seeded attribution (recommended):
     *   php artisan crewly:normalize-demo-seeded-created-by --dry-run
     * - Set seeded attribution to user 1 (if you explicitly want a system user):
     *   php artisan crewly:normalize-demo-seeded-created-by --set-to-user=1
     */
    protected $signature = 'crewly:normalize-demo-seeded-created-by
        {--set-to-user= : If provided, sets created_by/created_by_user_id to this user id (otherwise sets to null)}
        {--dry-run : Show counts only; do not update}';

    protected $description = 'Normalize created_by fields for seeded demo data so onboarding counts only real user actions.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $setToUserRaw = $this->option('set-to-user');

        $targetUserId = null;
        if ($setToUserRaw !== null && $setToUserRaw !== '') {
            $targetUserId = (int) $setToUserRaw;
            if ($targetUserId < 1) {
                $this->error('Invalid --set-to-user value.');
                return self::FAILURE;
            }

            $exists = User::query()->whereKey($targetUserId)->exists();
            if (! $exists) {
                $this->error("User id {$targetUserId} does not exist.");
                return self::FAILURE;
            }
        }

        $demoCompanyIds = Company::query()
            ->where('is_demo', true)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();

        if (count($demoCompanyIds) === 0) {
            $this->info('No demo companies found (is_demo = true).');
            return self::SUCCESS;
        }

        $this->info('Demo companies: ' . count($demoCompanyIds));
        $this->info('Mode: ' . ($targetUserId ? "set-to-user={$targetUserId}" : 'set-to-null') . ($dryRun ? ' (dry-run)' : ''));

        $totalAffected = 0;

        // Employees: seeded demo employees use @demo.crewly.test.
        $totalAffected += $this->normalizeTable(
            table: 'employees',
            companyIds: $demoCompanyIds,
            companyColumn: 'company_id',
            createdByColumn: 'created_by',
            where: fn ($q) => $q->where('email', 'like', '%@demo.crewly.test'),
            targetUserId: $targetUserId,
            dryRun: $dryRun
        );

        // Leave Types: seeded demo leave types are the canonical VL/SL/EL/UL.
        $totalAffected += $this->normalizeTable(
            table: 'leave_types',
            companyIds: $demoCompanyIds,
            companyColumn: 'company_id',
            createdByColumn: 'created_by',
            where: fn ($q) => $q->whereIn('code', ['VL', 'SL', 'EL', 'UL']),
            targetUserId: $targetUserId,
            dryRun: $dryRun
        );

        // Memo Templates: seeded demo templates have fixed slugs.
        $totalAffected += $this->normalizeTable(
            table: 'memo_templates',
            companyIds: $demoCompanyIds,
            companyColumn: 'company_id',
            createdByColumn: 'created_by_user_id',
            where: fn ($q) => $q->whereIn('slug', ['notice-to-explain', 'written-warning']),
            targetUserId: $targetUserId,
            dryRun: $dryRun
        );

        $this->info('Total affected rows: ' . $totalAffected . ($dryRun ? ' (estimated)' : ''));

        return self::SUCCESS;
    }

    /**
     * @param  callable  $where function(\Illuminate\Database\Query\Builder $q): void
     */
    private function normalizeTable(
        string $table,
        array $companyIds,
        string $companyColumn,
        string $createdByColumn,
        callable $where,
        ?int $targetUserId,
        bool $dryRun
    ): int {
        if (!Schema::hasTable($table)) {
            $this->line("- {$table}: skipped (table missing)");
            return 0;
        }

        if (!Schema::hasColumn($table, $companyColumn) || !Schema::hasColumn($table, $createdByColumn)) {
            $this->line("- {$table}: skipped (missing {$companyColumn} or {$createdByColumn})");
            return 0;
        }

        $query = DB::table($table)
            ->whereIn($companyColumn, $companyIds);

        $where($query);

        if ($targetUserId === null) {
            $query->whereNotNull($createdByColumn);
        }

        $count = (int) $query->count();

        if ($count === 0) {
            $this->line("- {$table}: 0 rows");
            return 0;
        }

        if ($dryRun) {
            $this->line("- {$table}: {$count} rows (dry-run)");
            return $count;
        }

        $updated = (int) $query->update([$createdByColumn => $targetUserId]);
        $this->line("- {$table}: {$updated} rows updated");

        return $updated;
    }
}
