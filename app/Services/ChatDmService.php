<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Repositories\ConversationParticipantRepository;
use App\Repositories\ConversationRepository;
use Illuminate\Support\Facades\DB;

class ChatDmService extends Service
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationParticipantRepository $participantRepository,
        private readonly AuditLogger $auditLogger,
    )
    {
    }

    public function openOrCreate(User $user, User $other): Conversation
    {
        // Defense in depth: prevent cross-company DMs.
        if (!$user->isDeveloper() && !$other->isDeveloper()) {
            if ($user->company_id && $other->company_id && (int) $user->company_id !== (int) $other->company_id) {
                throw new \DomainException('Cross-company direct messages are not allowed.');
            }
        }

        return DB::transaction(function () use ($user, $other) {
            $existing = $this->conversationRepository->findDmBetween((int) $user->id, (int) $other->id);
            if ($existing) {
                return $existing;
            }

            $conversation = $this->conversationRepository->createDm((int) $user->id);

            $this->participantRepository->addParticipant((int) $conversation->id, (int) $user->id, 'OWNER');
            $this->participantRepository->addParticipant((int) $conversation->id, (int) $other->id, 'MEMBER');

            $this->auditLogger->log(
                'chat.dm.created',
                $conversation,
                [],
                ['type' => 'DM'],
                ['other_user_id' => (int) $other->id, 'other_role' => (string) $other->role()]
            );

            return $conversation;
        });
    }
}
