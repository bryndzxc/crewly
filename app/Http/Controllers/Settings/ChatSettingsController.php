<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\DTO\ChatSoundPreferenceData;
use App\Services\ChatSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatSettingsController extends Controller
{
    public function __construct(
        private readonly ChatSettingsService $chatSettingsService,
    ) {}

    public function updateSound(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $dto = ChatSoundPreferenceData::fromArray($validated);

        $payload = $this->chatSettingsService->updateSound($user, $dto);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('success', 'Chat sound preference updated.')->setStatusCode(303);
    }
}
