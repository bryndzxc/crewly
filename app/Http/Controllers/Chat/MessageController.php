<?php

namespace App\Http\Controllers\Chat;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
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

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $data['body'],
            'type' => 'text',
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        // Ensure sender isn't considered unread
        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $recipientRoles = $conversation
            ->users()
            ->where('users.id', '!=', $user->id)
            ->pluck('role')
            ->unique()
            ->values()
            ->all();

        app(AuditLogger::class)->log(
            'chat.message.sent',
            $message,
            [],
            ['conversation_id' => $conversation->id, 'type' => $conversation->type, 'body' => '[CHAT_MESSAGE]'],
            ['recipient_roles' => $recipientRoles]
        );

        broadcast(new MessageSent($message))->toOthers();

        $message->loadMissing(['sender:id,name']);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'body' => $message->body,
                'type' => $message->type,
                'sender' => [
                    'id' => $message->sender?->id,
                    'name' => $message->sender?->name,
                ],
                'created_at' => $message->created_at?->toISOString(),
            ],
        ]);
    }
}
