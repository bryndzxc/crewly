<?php

namespace App\Http\Controllers\Developer;

use App\DTO\DeveloperCompanyWithUserCreateData;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\DeveloperCompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct(private readonly DeveloperCompanyService $developerCompanyService)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Developer/Companies/Index', $this->developerCompanyService->index($request));
    }

    public function show(Request $request, Company $company): Response
    {
        return Inertia::render('Developer/Companies/Show', $this->developerCompanyService->show($request, $company));
    }

    public function create(): Response
    {
        return Inertia::render('Developer/Companies/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company.name' => ['required', 'string', 'max:255'],
            'company.slug' => ['nullable', 'string', 'max:255'],
            'company.timezone' => ['nullable', 'string', 'max:255'],
            'company.is_active' => ['nullable', 'boolean'],

            'user.name' => ['required', 'string', 'max:255'],
            'user.email' => ['required', 'email', 'max:255'],
            'user.password' => ['required', 'string', 'min:8', 'max:255'],
            'user.role' => ['nullable', 'in:admin,hr,manager,employee'],
        ]);

        $data = DeveloperCompanyWithUserCreateData::fromArray($validated);
        $company = $this->developerCompanyService->createCompanyWithInitialUser($data);

        return redirect()
            ->route('developer.companies.index')
            ->with('success', "Company '{$company->name}' created.")
            ->setStatusCode(303);
    }

    public function storeUser(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'user.name' => ['required', 'string', 'max:255'],
            'user.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'user.password' => ['required', 'string', 'min:8', 'max:255'],
            'user.role' => ['nullable', 'in:admin,hr,manager,employee'],
        ]);

        $user = $this->developerCompanyService->createUserForCompany($company, (array) ($validated['user'] ?? []));

        return redirect()
            ->route('developer.companies.show', $company)
            ->with('success', "User '{$user->email}' added to '{$company->name}'.")
            ->setStatusCode(303);
    }
}
