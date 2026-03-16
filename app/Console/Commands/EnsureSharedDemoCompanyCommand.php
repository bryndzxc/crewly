<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\DeveloperLeadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnsureSharedDemoCompanyCommand extends Command
{
    protected $signature = 'demo:ensure-shared
        {--seed : Seed sample data if the company has no employees yet}
        {--force-password : Overwrite the demo user password even if user already exists}';

    protected $description = 'Ensure the shared live demo company + HR user exist (Option A).';

    public function handle(): int
    {
        $enabled = (bool) config('crewly.demo.shared.enabled', true);
        if (! $enabled) {
            $this->info('Shared demo is disabled (CREWLY_DEMO_SHARED_ENABLED=false).');
            return self::SUCCESS;
        }

        $companyName = trim((string) config('crewly.demo.shared.company_name', 'Crewly Demo'));
        $companySlug = trim((string) config('crewly.demo.shared.company_slug', 'crewly-demo'));
        $userName = trim((string) config('crewly.demo.shared.user_name', 'Demo HR'));
        $userEmail = Str::lower(trim((string) config('crewly.demo.shared.user_email', '')));
        $userPassword = (string) (config('crewly.demo.shared.user_password') ?? '');
        $maxEmployees = max(1, (int) config('crewly.demo.shared.max_employees', 100));

        if ($companySlug === '') {
            $this->error('Missing demo company slug. Set CREWLY_DEMO_SHARED_COMPANY_SLUG.');
            return self::FAILURE;
        }

        if ($userEmail === '') {
            $this->error('Missing demo user email. Set CREWLY_DEMO_SHARED_EMAIL.');
            return self::FAILURE;
        }

        $existingUser = User::withTrashed()->where('email', $userEmail)->first();
        if (! $existingUser && trim($userPassword) === '') {
            $this->error('Missing demo user password. Set CREWLY_DEMO_SHARED_PASSWORD before creating the shared demo user.');
            return self::FAILURE;
        }

        /** @var array{0:Company,1:User} $result */
        $result = DB::transaction(function () use ($companyName, $companySlug, $userName, $userEmail, $userPassword, $maxEmployees) {
            $company = Company::query()->where('slug', $companySlug)->first();

            if (! $company) {
                $company = Company::query()->create([
                    'name' => $companyName !== '' ? $companyName : 'Crewly Demo',
                    'slug' => $companySlug,
                    'timezone' => (string) config('app.timezone', 'Asia/Manila'),
                    'is_active' => true,
                    'is_demo' => true,
                    'plan_name' => Company::PLAN_PRO,
                    'max_employees' => $maxEmployees,
                    'subscription_status' => Company::SUB_ACTIVE,
                ]);
            } else {
                $company->forceFill([
                    'name' => $companyName !== '' ? $companyName : (string) ($company->name ?? 'Crewly Demo'),
                    'timezone' => (string) ($company->timezone ?? config('app.timezone', 'Asia/Manila')),
                    'is_active' => true,
                    'is_demo' => true,
                    'plan_name' => $company->plan_name ?: Company::PLAN_PRO,
                    'max_employees' => $maxEmployees,
                    'subscription_status' => $company->subscription_status ?: Company::SUB_ACTIVE,
                ])->save();
            }

            $user = User::withTrashed()->where('email', $userEmail)->first();

            if (! $user) {
                $user = User::query()->create([
                    'company_id' => (int) $company->id,
                    'name' => $userName !== '' ? $userName : 'Demo HR',
                    'email' => $userEmail,
                    'role' => User::ROLE_HR,
                    'password' => Hash::make($userPassword),
                    'must_change_password' => false,
                ]);

                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();
            } else {
                if ($user->trashed()) {
                    $user->restore();
                }

                $user->forceFill([
                    'company_id' => (int) $company->id,
                    'name' => $userName !== '' ? $userName : (string) ($user->name ?? 'Demo HR'),
                    'role' => User::ROLE_HR,
                    'must_change_password' => false,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);

                $shouldOverwritePassword = (bool) $this->option('force-password');
                if ($userPassword !== '' && $shouldOverwritePassword) {
                    $user->forceFill(['password' => Hash::make($userPassword)]);
                }

                $user->save();
            }

            return [$company, $user];
        });

        [$company, $user] = $result;

        $this->info("Shared demo company ensured: {$company->name} ({$company->slug}) [max_employees={$company->max_employees}]");
        $this->info("Shared demo user ensured: {$user->email} (role={$user->role})");

        if ((bool) $this->option('seed')) {
            app(DeveloperLeadService::class)->seedDemoCompanyData($company, $user);
            $this->info('Seeded demo company data (if empty).');

            $exit = (int) $this->call('crewly:seed-government-contributions');
            if ($exit !== self::SUCCESS) {
                $this->warn('Government contributions seeding returned a non-success exit code.');
            }
        }

        return self::SUCCESS;
    }
}
