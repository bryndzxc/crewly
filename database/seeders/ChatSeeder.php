<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        // Seed per-company channels to avoid cross-tenant leakage.
        $companies = Company::query()->select(['id'])->get();

        foreach ($companies as $company) {
            $announcements = Conversation::query()->updateOrCreate(
                ['type' => Conversation::TYPE_CHANNEL, 'slug' => 'announcements-' . $company->id],
                ['name' => 'Announcements']
            );

            $hrTeam = Conversation::query()->updateOrCreate(
                ['type' => Conversation::TYPE_CHANNEL, 'slug' => 'hr-team-' . $company->id],
                ['name' => 'HR Team']
            );

            $users = User::query()->select(['id', 'role', 'company_id'])->where('company_id', $company->id)->get();

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

        // Note: legacy global slugs ('announcements', 'hr-team') are intentionally not seeded.
    }
}
