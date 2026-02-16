<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
		if ($request->user()?->hasRole(User::ROLE_EMPLOYEE)) {
			abort(403);
		}

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        if ($request->user()?->hasRole(User::ROLE_EMPLOYEE)) {
            abort(403);
        }

        $validated = $request->validate([
            'photo' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png'],
        ]);

        $file = $validated['photo'];
        if (!$file instanceof \Illuminate\Http\UploadedFile) {
            return back()->with('error', 'Invalid photo upload.')->setStatusCode(303);
        }

        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $old = (string) ($user->getAttribute('profile_photo_path') ?? '');
        if (trim($old) !== '') {
            try {
                Storage::disk('public')->delete($old);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: 'jpg'));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $ext = 'jpg';
        }

        $uuid = (string) Str::uuid();
        $directory = "users/{$user->id}/profile";
        $filename = "{$uuid}.{$ext}";
        $relativePath = "{$directory}/{$filename}";

        $file->storeAs($directory, $filename, 'public');

        $user->forceFill(['profile_photo_path' => $relativePath])->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Profile photo updated successfully.')
            ->setStatusCode(303);
    }

    public function showPhoto(Request $request): SymfonyResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $path = (string) ($user->getAttribute('profile_photo_path') ?? '');
        if (trim($path) === '') {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {

        abort(403);
    }
}
