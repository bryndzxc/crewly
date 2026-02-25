<?php

namespace App\Http\Controllers\Chat;

use App\DTO\ChatMessageSendData;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function __construct(
        private readonly ChatMessageService $chatMessageService,
    ) {}

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $demoEmail = strtolower((string) config('crewly.demo.email', 'demo@crewly.test'));
        if ($demoEmail !== '' && strtolower((string) ($user->email ?? '')) === $demoEmail) {
            abort(403, 'Demo account cannot send messages.');
        }

        $conversation = Conversation::query()->findOrFail($id);
        $this->authorize('sendMessage', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $dto = ChatMessageSendData::fromArray($validated);
        if ($dto->body === '') {
            throw ValidationException::withMessages(['body' => 'Message body is required.']);
        }

        $payload = $this->chatMessageService->sendTextMessage($user, $conversation, $dto->body);

        return response()->json($payload);
    }
}
