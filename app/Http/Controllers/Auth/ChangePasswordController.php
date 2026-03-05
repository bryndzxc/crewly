<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChangePasswordController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $sharedEmail = strtolower(trim((string) config('crewly.demo.shared.user_email', '')));
        $isSharedDemo = $user
            && $sharedEmail !== ''
            && strtolower(trim((string) ($user->email ?? ''))) === $sharedEmail
            && $user->company
            && (bool) ($user->company->is_demo ?? false);

        if ($isSharedDemo) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Demo mode: password changes are disabled for the shared demo account.')
                ->setStatusCode(303);
        }

        return Inertia::render('Auth/ChangePassword');
    }
}
