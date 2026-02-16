<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatSettingsController extends Controller
{
    public function updateSound(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $user->forceFill([
            'chat_sound_enabled' => (bool) $data['enabled'],
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'chat_sound_enabled' => (bool) $user->chat_sound_enabled,
            ]);
        }

        return back()->with('success', 'Chat sound preference updated.')->setStatusCode(303);
    }
}
