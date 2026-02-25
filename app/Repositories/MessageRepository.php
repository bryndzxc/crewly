<?php

namespace App\Repositories;

use App\Models\Message;

class MessageRepository
{
    public function createTextMessage(int $conversationId, int $userId, string $body): Message
    {
        /** @var Message $message */
        $message = Message::query()->create([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'body' => $body,
            'type' => 'text',
        ]);

        return $message;
    }
}
