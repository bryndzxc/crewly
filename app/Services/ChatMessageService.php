<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\ConversationParticipantRepository;
use App\Repositories\MessageRepository;
use Illuminate\Support\Facades\DB;

class ChatMessageService extends Service
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ConversationParticipantRepository $participantRepository,
        private readonly AuditLogger $auditLogger,
    )
    {
    }

    /**
     * @return array{message:array<string,mixed>}
     */
    public function sendTextMessage(User $user, Conversation $conversation, string $body): array
    {
        return DB::transaction(function () use ($user, $conversation, $body) {
            $message = $this->messageRepository->createTextMessage((int) $conversation->id, (int) $user->id, $body);

            $conversation->forceFill(['last_message_at' => now()])->save();

            // Ensure sender isn't considered unread
            $this->participantRepository->markRead((int) $conversation->id, (int) $user->id);

            $recipientRoles = $conversation
                ->users()
                ->where('users.id', '!=', $user->id)
                ->pluck('role')
                ->unique()
                ->values()
                ->all();

            $this->auditLogger->log(
                'chat.message.sent',
                $message,
                [],
                ['conversation_id' => (int) $conversation->id, 'type' => (string) $conversation->type, 'body' => '[CHAT_MESSAGE]'],
                ['recipient_roles' => $recipientRoles]
            );

            broadcast(new MessageSent($message))->toOthers();

            $message->loadMissing(['sender:id,name']);

            return [
                'message' => $this->toPayload($message),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function toPayload(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'body' => $message->body,
            'type' => $message->type,
            'sender' => [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name,
            ],
            'created_at' => $message->created_at?->toISOString(),
        ];
    }
}
