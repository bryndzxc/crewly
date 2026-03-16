<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\GovernmentDefaultsService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class GovernmentDefaultsController extends Controller
{
    public function loadSss2025Defaults(GovernmentDefaultsService $service): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        try {
            $service->loadSss2025();
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Failed to load default 2025 rates.')->setStatusCode(303);
        }

        return redirect()->back()->with('success', 'Default 2025 rates loaded successfully.')->setStatusCode(303);
    }

    public function loadPhilHealth2025Defaults(GovernmentDefaultsService $service): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        try {
            $service->loadPhilhealth2025();
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Failed to load default 2025 rates.')->setStatusCode(303);
        }

        return redirect()->back()->with('success', 'Default 2025 rates loaded successfully.')->setStatusCode(303);
    }

    public function loadPagibig2025Defaults(GovernmentDefaultsService $service): RedirectResponse
    {
        $this->authorize('manage-government-contributions');

        try {
            $service->loadPagibig2025();
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Failed to load default 2025 rates.')->setStatusCode(303);
        }

        return redirect()->back()->with('success', 'Default 2025 rates loaded successfully.')->setStatusCode(303);
    }
}
