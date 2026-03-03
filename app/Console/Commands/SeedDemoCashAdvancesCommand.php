<?php

namespace App\Console\Commands;

use App\Models\CashAdvance;
use App\Models\CashAdvanceDeduction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SeedDemoCashAdvancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crewly:seed-demo-cash-advances {--force : Seed even if the company already has cash advances}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed sample cash advances and deductions for all demo companies.';

    public function handle(): int
    {
        if (!Schema::hasTable('companies')) {
            $this->error("Missing 'companies' table.");

            return self::FAILURE;
        }

        if (!Schema::hasTable('cash_advances') || !Schema::hasTable('cash_advance_deductions')) {
            $this->error("Missing cash advance tables. Run migrations first.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        /** @var \Illuminate\Support\Collection<int, Company> $companies */
        $companies = Company::query()
            ->where('is_demo', true)
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'is_demo']);

        if ($companies->count() === 0) {
            $this->info('No demo companies found.');

            return self::SUCCESS;
        }

        $seeded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($companies as $company) {
            $companyId = (int) $company->id;

            try {
                $existing = (int) CashAdvance::withoutCompanyScope()
                    ->where('company_id', $companyId)
                    ->count();

                if ($existing > 0 && !$force) {
                    $this->line("- Skipped: {$company->name} (already has cash advances)");
                    $skipped++;
                    continue;
                }

                $employees = Employee::withoutCompanyScope()
                    ->where('company_id', $companyId)
                    ->orderBy('employee_id', 'asc')
                    ->limit(3)
                    ->get(['employee_id']);

                if ($employees->count() === 0) {
                    $this->warn("- Skipped: {$company->name} (no employees)");
                    $skipped++;
                    continue;
                }

                $actorUserId = User::query()->where('company_id', $companyId)->orderBy('id', 'asc')->value('id');
                $actorUserId = $actorUserId ? (int) $actorUserId : null;

                $this->seedCompany((int) $companyId, $employees->pluck('employee_id')->map(fn ($v) => (int) $v)->all(), $actorUserId);

                $this->info("- Seeded: {$company->name}");
                $seeded++;
            } catch (\Throwable $e) {
                $this->error("- Failed: {$company->name} ({$e->getMessage()})");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. Seeded={$seeded}, Skipped={$skipped}, Failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param array<int, int> $employeeIds
     */
    private function seedCompany(int $companyId, array $employeeIds, ?int $actorUserId): void
    {
        $today = Carbon::today();

        $e0 = $employeeIds[0] ?? null;
        $e1 = $employeeIds[1] ?? $e0;
        $e2 = $employeeIds[2] ?? $e0;

        if (!$e0) {
            return;
        }

        // Approved + partially paid (active).
        $active = CashAdvance::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'employee_id' => (int) $e0,
            'amount' => 5000,
            'reason' => 'Emergency cash advance (sample data).',
            'requested_at' => $today->copy()->subDays(12)->toDateString(),
            'status' => CashAdvance::STATUS_APPROVED,
            'requested_by' => $actorUserId,
            'approved_by' => $actorUserId,
            'approved_at' => $today->copy()->subDays(10)->startOfDay(),
            'decision_remarks' => 'Approved for payroll deduction (seeded for demo).',
            'installment_amount' => 1000,
            'installments_count' => 5,
        ]);

        CashAdvanceDeduction::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'cash_advance_id' => (int) $active->id,
            'deducted_at' => $today->copy()->subDays(7)->toDateString(),
            'amount' => 1000,
            'notes' => 'Payroll deduction (sample).',
            'payroll_run_id' => null,
            'created_by' => $actorUserId,
        ]);

        // Pending request.
        CashAdvance::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'employee_id' => (int) $e1,
            'amount' => 2500,
            'reason' => 'Medical expense advance request (sample data).',
            'requested_at' => $today->copy()->subDays(3)->toDateString(),
            'status' => CashAdvance::STATUS_PENDING,
            'requested_by' => $actorUserId,
        ]);

        // Completed (fully paid) sample.
        $completed = CashAdvance::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'employee_id' => (int) $e2,
            'amount' => 3000,
            'reason' => 'Uniform + tools (sample data).',
            'requested_at' => $today->copy()->subDays(40)->toDateString(),
            'status' => CashAdvance::STATUS_COMPLETED,
            'requested_by' => $actorUserId,
            'approved_by' => $actorUserId,
            'approved_at' => $today->copy()->subDays(38)->startOfDay(),
            'decision_remarks' => 'Approved and fully repaid (seeded for demo).',
            'installment_amount' => 1500,
            'installments_count' => 2,
            'completed_by' => $actorUserId,
            'completed_at' => $today->copy()->subDays(8)->endOfDay(),
        ]);

        CashAdvanceDeduction::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'cash_advance_id' => (int) $completed->id,
            'deducted_at' => $today->copy()->subDays(30)->toDateString(),
            'amount' => 1500,
            'notes' => 'Payroll deduction (sample).',
            'payroll_run_id' => null,
            'created_by' => $actorUserId,
        ]);

        CashAdvanceDeduction::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'cash_advance_id' => (int) $completed->id,
            'deducted_at' => $today->copy()->subDays(15)->toDateString(),
            'amount' => 1500,
            'notes' => 'Payroll deduction (sample).',
            'payroll_run_id' => null,
            'created_by' => $actorUserId,
        ]);
    }
}
