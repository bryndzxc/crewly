<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\User;
use App\Services\DeveloperLeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SeedDemoCompanyDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $createdByUserId,
    ) {
    }

    public function handle(DeveloperLeadService $developerLeadService): void
    {
        /** @var Company|null $company */
        $company = Company::query()->find($this->companyId);

        /** @var User|null $user */
        $user = User::withTrashed()
            ->withoutCompanyScope()
            ->find($this->createdByUserId);

        if (!$company || !$user) {
            return;
        }

        $developerLeadService->seedDemoCompanyData($company, $user);
    }
}
