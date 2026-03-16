<?php

namespace App\Http\Controllers\Settings\GovernmentContributions;

use App\Http\Controllers\Controller;
use App\Models\PagibigContributionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PagibigContributionSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage-government-contributions');

        $items = PagibigContributionSetting::query()
            ->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Settings/GovernmentContributions/PagIBIG/Index', [
            'settings' => $items->map(fn (PagibigContributionSetting $s) => [
                'id' => (int) $s->id,
                'effective_from' => $s->effective_from?->format('Y-m-d'),
                'effective_to' => $s->effective_to?->format('Y-m-d'),
                'employee_rate_below_threshold' => $s->employee_rate_below_threshold !== null ? (float) $s->employee_rate_below_threshold : null,
                'employee_rate_above_threshold' => $s->employee_rate_above_threshold !== null ? (float) $s->employee_rate_above_threshold : null,
                'employer_rate' => $s->employer_rate !== null ? (float) $s->employer_rate : null,
                'salary_threshold' => $s->salary_threshold !== null ? (float) $s->salary_threshold : null,
                'monthly_cap' => $s->monthly_cap !== null ? (float) $s->monthly_cap : null,
                'notes' => $s->notes,
            ])->values()->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manage-government-contributions');

        return Inertia::render('Settings/GovernmentContributions/PagIBIG/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $validated = $this->validatePayload($request);

        PagibigContributionSetting::query()->create($validated);

        return redirect()->route('settings.government_contributions.pagibig.index')->with('success', 'Pag-IBIG setting added.');
    }

    public function edit(Request $request, PagibigContributionSetting $setting): Response
    {
        $this->authorize('manage-government-contributions');

        return Inertia::render('Settings/GovernmentContributions/PagIBIG/Edit', [
            'setting' => [
                'id' => (int) $setting->id,
                'effective_from' => $setting->effective_from?->format('Y-m-d'),
                'effective_to' => $setting->effective_to?->format('Y-m-d'),
                'employee_rate_below_threshold' => $setting->employee_rate_below_threshold !== null ? (float) $setting->employee_rate_below_threshold : null,
                'employee_rate_above_threshold' => $setting->employee_rate_above_threshold !== null ? (float) $setting->employee_rate_above_threshold : null,
                'employer_rate' => $setting->employer_rate !== null ? (float) $setting->employer_rate : null,
                'salary_threshold' => $setting->salary_threshold !== null ? (float) $setting->salary_threshold : null,
                'monthly_cap' => $setting->monthly_cap !== null ? (float) $setting->monthly_cap : null,
                'notes' => $setting->notes,
            ],
        ]);
    }

    public function update(Request $request, PagibigContributionSetting $setting): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $validated = $this->validatePayload($request);

        $setting->fill($validated);
        $setting->save();

        return redirect()->route('settings.government_contributions.pagibig.index')->with('success', 'Pag-IBIG setting updated.');
    }

    public function archive(Request $request, PagibigContributionSetting $setting): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $request->validate([
            'effective_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $effectiveTo = $request->input('effective_to');
        $setting->effective_to = $effectiveTo ?: now()->toDateString();
        $setting->save();

        return redirect()->route('settings.government_contributions.pagibig.index')->with('success', 'Pag-IBIG setting archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'employee_rate_below_threshold' => ['nullable', 'numeric', 'min:0'],
            'employee_rate_above_threshold' => ['nullable', 'numeric', 'min:0'],
            'employer_rate' => ['nullable', 'numeric', 'min:0'],
            'salary_threshold' => ['nullable', 'numeric', 'min:0'],
            'monthly_cap' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
