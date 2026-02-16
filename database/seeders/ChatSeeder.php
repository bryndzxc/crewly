<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $announcements = Conversation::query()->updateOrCreate(
            ['type' => Conversation::TYPE_CHANNEL, 'slug' => 'announcements'],
            ['name' => 'Announcements']
        );

        $hrTeam = Conversation::query()->updateOrCreate(
            ['type' => Conversation::TYPE_CHANNEL, 'slug' => 'hr-team'],
            ['name' => 'HR Team']
        );

        $users = User::query()->select(['id', 'role'])->get();

        foreach ($users as $user) {
            ConversationParticipant::query()->updateOrCreate(
                ['conversation_id' => $announcements->id, 'user_id' => $user->id],
                ['role_in_conversation' => 'MEMBER']
            );

            if (in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR], true)) {
                ConversationParticipant::query()->updateOrCreate(
                    ['conversation_id' => $hrTeam->id, 'user_id' => $user->id],
                    ['role_in_conversation' => 'MEMBER']
                );
            }
        }
    }
}
