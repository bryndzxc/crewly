<?php

namespace App\Services;

use App\DTO\DeveloperCompanyWithUserCreateData;
use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeveloperCompanyService extends Service
{
    public function __construct(private readonly CompanyRepository $companyRepository)
    {
    }

    public function index(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $companies = $this->companyRepository->paginate($perPage);

        $companies->setCollection(
            $companies->getCollection()->map(fn (Company $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'slug' => (string) $c->slug,
                'timezone' => (string) ($c->timezone ?? ''),
                'is_active' => (bool) $c->is_active,
            ])
        );

        return [
            'filters' => [
                'per_page' => $perPage,
            ],
            'companies' => $companies,
        ];
    }

    public function show(Request $request, Company $company): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $users = $this->companyRepository->paginateUsersForCompany($company, $perPage);
        $users->setCollection(
            $users->getCollection()->map(fn (User $u) => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
                'role' => (string) ($u->role ?? ''),
            ])
        );

        return [
            'filters' => [
                'per_page' => $perPage,
            ],
            'company' => [
                'id' => (int) $company->id,
                'name' => (string) $company->name,
                'slug' => (string) $company->slug,
                'timezone' => (string) ($company->timezone ?? ''),
                'is_active' => (bool) $company->is_active,
            ],
            'users' => $users,
        ];
    }

    public function createCompanyWithInitialUser(DeveloperCompanyWithUserCreateData $data): Company
    {
        return DB::transaction(function () use ($data) {
            $companyAttributes = $data->company()->toArray();

            if (($companyAttributes['slug'] ?? '') === '') {
                $companyAttributes['slug'] = Str::slug((string) ($companyAttributes['name'] ?? ''));
            }

            if (($companyAttributes['timezone'] ?? null) === null) {
                $companyAttributes['timezone'] = (string) config('app.timezone', 'Asia/Manila');
            }

            $company = $this->companyRepository->createCompany($companyAttributes);

            $user = $data->user();
            $role = (string) ($user['role'] ?? User::ROLE_MANAGER);
            if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER, User::ROLE_EMPLOYEE], true)) {
                $role = User::ROLE_MANAGER;
            }

            $this->companyRepository->createUserForCompany($company, [
                'name' => (string) $user['name'],
                'email' => (string) $user['email'],
                'role' => $role,
                'password' => Hash::make((string) $user['password']),
                'email_verified_at' => now(),
            ]);

            return $company;
        });
    }
}
