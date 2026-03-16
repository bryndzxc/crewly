<?php

namespace App\Http\Controllers\Settings\GovernmentContributions;

use App\Http\Controllers\Controller;
use App\Models\PhilhealthContributionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PhilhealthContributionSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage-government-contributions');

        $items = PhilhealthContributionSetting::query()
            ->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Settings/GovernmentContributions/PhilHealth/Index', [
            'settings' => $items->map(fn (PhilhealthContributionSetting $s) => [
                'id' => (int) $s->id,
                'effective_from' => $s->effective_from?->format('Y-m-d'),
                'effective_to' => $s->effective_to?->format('Y-m-d'),
                'premium_rate' => (float) ($s->premium_rate ?? 0),
                'salary_floor' => (float) ($s->salary_floor ?? 0),
                'salary_ceiling' => (float) ($s->salary_ceiling ?? 0),
                'employee_share_percent' => (float) ($s->employee_share_percent ?? 0),
                'employer_share_percent' => (float) ($s->employer_share_percent ?? 0),
                'notes' => $s->notes,
            ])->values()->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manage-government-contributions');

        return Inertia::render('Settings/GovernmentContributions/PhilHealth/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $validated = $this->validatePayload($request);

        PhilhealthContributionSetting::query()->create($validated);

        return redirect()->route('settings.government_contributions.philhealth.index')->with('success', 'PhilHealth setting added.');
    }

    public function edit(Request $request, PhilhealthContributionSetting $setting): Response
    {
        $this->authorize('manage-government-contributions');

        return Inertia::render('Settings/GovernmentContributions/PhilHealth/Edit', [
            'setting' => [
                'id' => (int) $setting->id,
                'effective_from' => $setting->effective_from?->format('Y-m-d'),
                'effective_to' => $setting->effective_to?->format('Y-m-d'),
                'premium_rate' => (float) ($setting->premium_rate ?? 0),
                'salary_floor' => (float) ($setting->salary_floor ?? 0),
                'salary_ceiling' => (float) ($setting->salary_ceiling ?? 0),
                'employee_share_percent' => (float) ($setting->employee_share_percent ?? 0),
                'employer_share_percent' => (float) ($setting->employer_share_percent ?? 0),
                'notes' => $setting->notes,
            ],
        ]);
    }

    public function update(Request $request, PhilhealthContributionSetting $setting): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $validated = $this->validatePayload($request);

        $setting->fill($validated);
        $setting->save();

        return redirect()->route('settings.government_contributions.philhealth.index')->with('success', 'PhilHealth setting updated.');
    }

    public function archive(Request $request, PhilhealthContributionSetting $setting): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        $request->validate([
            'effective_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $effectiveTo = $request->input('effective_to');
        $setting->effective_to = $effectiveTo ?: now()->toDateString();
        $setting->save();

        return redirect()->route('settings.government_contributions.philhealth.index')->with('success', 'PhilHealth setting archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'premium_rate' => ['required', 'numeric', 'min:0'],
            'salary_floor' => ['required', 'numeric', 'min:0'],
            'salary_ceiling' => ['required', 'numeric', 'gte:salary_floor'],
            'employee_share_percent' => ['required', 'numeric', 'min:0'],
            'employer_share_percent' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
