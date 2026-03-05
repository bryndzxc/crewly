<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function landing(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Public/Landing', [
            'shared_demo' => $this->sharedDemoPayload(),
        ]);
    }

    public function pricing(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Public/Pricing');
    }

    public function demo(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Public/Demo', [
            'shared_demo' => $this->sharedDemoPayload(includePassword: true),
        ]);
    }

    public function demoLogin(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        if (!config('crewly.demo.shared.enabled', true)) {
            return redirect()->route('login');
        }

        $email = trim((string) config('crewly.demo.shared.user_email', ''));
        if ($email === '') {
            return redirect()->route('login');
        }

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return redirect()->route('login')->with('status', 'Demo account is not configured yet.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * @return array{enabled:bool,email:?string,password:?string,max_employees:int,company_name:?string}
     */
    private function sharedDemoPayload(bool $includePassword = false): array
    {
        $enabled = (bool) config('crewly.demo.shared.enabled', true);
        $email = trim((string) config('crewly.demo.shared.user_email', ''));
        $password = $includePassword ? (string) (config('crewly.demo.shared.user_password') ?? '') : '';

        return [
            'enabled' => $enabled,
            'email' => $email !== '' ? $email : null,
            'password' => $includePassword && $password !== '' ? $password : null,
            'max_employees' => max(1, (int) config('crewly.demo.shared.max_employees', 100)),
            'company_name' => trim((string) config('crewly.demo.shared.company_name', '')) ?: null,
        ];
    }

    public function privacy(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Public/Privacy');
    }

    public function terms(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Public/Terms');
    }
}
