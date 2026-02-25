<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $wasForced = (bool) ($user->must_change_password ?? false);

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        if ($wasForced) {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Password updated successfully.')
                ->setStatusCode(303);
        }

        $role = $user->getAttributes()['role'] ?? null;
        if ($role === User::ROLE_EMPLOYEE) {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Password updated successfully.')
                ->setStatusCode(303);
        }

        return back()->with('success', 'Password updated successfully.');
    }
}
