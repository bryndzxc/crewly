<?php

namespace App\Repositories;

use App\Models\Conversation;

class ConversationRepository
{
    public function findDmBetween(int $userId, int $otherUserId): ?Conversation
    {
        return Conversation::query()
            ->dms()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $otherUserId))
            ->withCount('participants')
            ->having('participants_count', '=', 2)
            ->first();
    }

    public function createDm(int $createdByUserId): Conversation
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_DM,
            'created_by' => $createdByUserId,
        ]);

        return $conversation;
    }
}
