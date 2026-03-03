<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyBillingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDeveloper();
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isDeveloper();
    }

    public function update(User $user, Company $company): bool
    {
        return $user->isDeveloper();
    }
}
