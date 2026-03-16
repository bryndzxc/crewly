<?php

namespace App\Http\Controllers\Settings\GovernmentContributions;

use App\Http\Controllers\Controller;
use App\Models\SssContributionTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SssContributionTableController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage-government-contributions');

        $rules = SssContributionTable::query()
            ->orderBy('effective_from', 'desc')
            ->orderBy('range_from', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Settings/GovernmentContributions/SSS/Index', [
            'rules' => $rules->map(fn (SssContributionTable $r) => [
                'id' => (int) $r->id,
                'effective_from' => $r->effective_from?->format('Y-m-d'),
                'effective_to' => $r->effective_to?->format('Y-m-d'),
                'range_from' => (float) ($r->range_from ?? 0),
                'range_to' => (float) ($r->range_to ?? 0),
                'monthly_salary_credit' => $r->monthly_salary_credit !== null ? (float) $r->monthly_salary_credit : null,
                'employee_share' => (float) ($r->employee_share ?? 0),
                'employer_share' => (float) ($r->employer_share ?? 0),
                'ec_share' => $r->ec_share !== null ? (float) $r->ec_share : null,
                'notes' => $r->notes,
            ])->values()->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manage-government-contributions');

        return Inertia::render('Settings/GovernmentContributions/SSS/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $validated = $this->validatePayload($request);

        SssContributionTable::query()->create($validated);

        return redirect()->route('settings.government_contributions.sss.index')->with('success', 'SSS contribution rule added.');
    }

    public function edit(Request $request, SssContributionTable $rule): Response
    {
        $this->authorize('manage-government-contributions');

        return Inertia::render('Settings/GovernmentContributions/SSS/Edit', [
            'rule' => [
                'id' => (int) $rule->id,
                'effective_from' => $rule->effective_from?->format('Y-m-d'),
                'effective_to' => $rule->effective_to?->format('Y-m-d'),
                'range_from' => (float) ($rule->range_from ?? 0),
                'range_to' => (float) ($rule->range_to ?? 0),
                'monthly_salary_credit' => $rule->monthly_salary_credit !== null ? (float) $rule->monthly_salary_credit : null,
                'employee_share' => (float) ($rule->employee_share ?? 0),
                'employer_share' => (float) ($rule->employer_share ?? 0),
                'ec_share' => $rule->ec_share !== null ? (float) $rule->ec_share : null,
                'notes' => $rule->notes,
            ],
        ]);
    }

    public function update(Request $request, SssContributionTable $rule): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $validated = $this->validatePayload($request);

        $rule->fill($validated);
        $rule->save();

        return redirect()->route('settings.government_contributions.sss.index')->with('success', 'SSS contribution rule updated.');
    }

    public function archive(Request $request, SssContributionTable $rule): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $request->validate([
            'effective_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $effectiveTo = $request->input('effective_to');
        $rule->effective_to = $effectiveTo ?: now()->toDateString();
        $rule->save();

        return redirect()->route('settings.government_contributions.sss.index')->with('success', 'SSS rule archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'range_from' => ['required', 'numeric', 'min:0'],
            'range_to' => ['required', 'numeric', 'gte:range_from'],
            'monthly_salary_credit' => ['nullable', 'numeric', 'min:0'],
            'employee_share' => ['required', 'numeric', 'min:0'],
            'employer_share' => ['required', 'numeric', 'min:0'],
            'ec_share' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
