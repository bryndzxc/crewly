<?php

namespace App\Repositories;

use App\Models\ConversationParticipant;

class ConversationParticipantRepository
{
    public function addParticipant(int $conversationId, int $userId, string $roleInConversation): ConversationParticipant
    {
        /** @var ConversationParticipant $participant */
        $participant = ConversationParticipant::query()->create([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'role_in_conversation' => $roleInConversation,
        ]);

        return $participant;
    }

    public function markRead(int $conversationId, int $userId): void
    {
        ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }
}
