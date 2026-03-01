<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CompanyRepository
{
    /**
     * @return Collection<int, Company>
     */
    public function allWithUsers(): Collection
    {
        return Company::query()
            ->orderBy('name')
            ->with(['users' => fn ($q) => $q->orderBy('name')->select(['id', 'company_id', 'name', 'email', 'role'])])
            ->get(['id', 'name', 'slug', 'timezone', 'is_active']);
    }

    public function paginateWithUsers(int $perPage = 10): LengthAwarePaginator
    {
        return Company::query()
            ->orderBy('name')
            ->with(['users' => fn ($q) => $q->orderBy('name')->select(['id', 'company_id', 'name', 'email', 'role'])])
            ->paginate($perPage, ['id', 'name', 'slug', 'timezone', 'is_active'])
            ->withQueryString();
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Company::query()
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'slug', 'timezone', 'is_active', 'is_demo'])
            ->withQueryString();
    }

    public function paginateUsersForCompany(Company $company, int $perPage = 10): LengthAwarePaginator
    {
        return $company
            ->users()
            ->orderBy('name')
            ->paginate($perPage, ['id', 'company_id', 'name', 'email', 'role'])
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createCompany(array $attributes): Company
    {
        /** @var Company $company */
        $company = Company::create($attributes);

        return $company;
    }

    public function createUserForCompany(Company $company, array $attributes): User
    {
        $attributes['company_id'] = (int) $company->id;

        /** @var User $user */
        $user = User::query()->create($attributes);

        return $user;
    }
}
