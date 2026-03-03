<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CrewlyNotification;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCompanySubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:sync-subscriptions {--dry-run : Do not persist changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily sync for manual subscription statuses (active/past_due/suspended).';

    public function handle(): int
    {
        $now = now();
        $dryRun = (bool) $this->option('dry-run');

        $pastDueSet = 0;
        $suspendedSet = 0;

        $companies = Company::query()
            ->whereIn('subscription_status', [Company::SUB_ACTIVE, Company::SUB_PAST_DUE])
            ->get(['id', 'name', 'subscription_status', 'next_billing_at', 'grace_days']);

        foreach ($companies as $company) {
            $status = strtolower(trim((string) ($company->subscription_status ?? '')));
            $nextBillingAt = $company->next_billing_at;
            $graceDays = (int) ($company->grace_days ?? 7);

            if (!$nextBillingAt) {
                continue;
            }

            if ($status === Company::SUB_ACTIVE && $nextBillingAt->lt($now)) {
                $pastDueSet++;
                $this->transition($company, Company::SUB_PAST_DUE, $dryRun, "Next billing date passed ({$nextBillingAt->format('Y-m-d')}).");
                continue;
            }

            if ($status === Company::SUB_PAST_DUE) {
                $suspendAt = (clone $nextBillingAt)->addDays($graceDays);
                if ($suspendAt->lt($now)) {
                    $suspendedSet++;
                    $this->transition($company, Company::SUB_SUSPENDED, $dryRun, "Grace period exceeded (next_billing_at={$nextBillingAt->format('Y-m-d')}, grace_days={$graceDays}).");
                }
            }
        }

        $this->info("billing:sync-subscriptions done. past_due={$pastDueSet}, suspended={$suspendedSet}" . ($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    private function transition(Company $company, string $toStatus, bool $dryRun, string $reason): void
    {
        $from = (string) ($company->subscription_status ?? '');
        $before = $company->getAttributes();

        Log::info('Billing subscription transition', [
            'company_id' => (int) $company->id,
            'from' => $from,
            'to' => $toStatus,
            'reason' => $reason,
            'dry_run' => $dryRun,
        ]);

        if ($dryRun) {
            return;
        }

        $company->subscription_status = $toStatus;
        $company->save();

        app(AuditLogger::class)->log(
            'billing.sync_transition',
            $company,
            $before,
            $company->getAttributes(),
            ['company_id' => (int) $company->id, 'from' => $from, 'to' => $toStatus],
            'Subscription status transitioned by daily sync.'
        );

        try {
            $ids = app(NotificationService::class)->hrAdminRecipientIdsForCompany((int) $company->id);
            if (count($ids) === 0) {
                return;
            }

            $title = $toStatus === Company::SUB_SUSPENDED ? 'Subscription suspended' : 'Billing past due';
            $body = $toStatus === Company::SUB_SUSPENDED
                ? 'Your subscription was suspended due to non-payment. Please contact support to restore access.'
                : 'Your billing is past due. Please contact support to settle your invoice.';

            foreach ($ids as $userId) {
                app(NotificationService::class)->createForUser(
                    userId: (int) $userId,
                    companyId: (int) $company->id,
                    type: $toStatus === Company::SUB_SUSPENDED ? 'BILLING_SUSPENDED' : 'BILLING_PAST_DUE',
                    title: $title,
                    body: $body,
                    url: null,
                    severity: $toStatus === Company::SUB_SUSPENDED ? CrewlyNotification::SEVERITY_DANGER : CrewlyNotification::SEVERITY_WARNING,
                    data: [
                        'company_id' => (int) $company->id,
                        'from' => $from,
                        'to' => $toStatus,
                        'reason' => $reason,
                        'next_billing_at' => $company->next_billing_at?->format('Y-m-d H:i:s'),
                        'grace_days' => (int) ($company->grace_days ?? 7),
                    ],
                    dedupeKey: null
                );
            }
        } catch (\Throwable $e) {
            // best-effort
        }
    }
}
