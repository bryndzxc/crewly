<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        if ($conversation->type === Conversation::TYPE_CHANNEL) {
            if ($conversation->slug === 'announcements') {
                return true;
            }

            if ($conversation->slug === 'hr-team') {
                return $user->isAdmin() || $user->isHR();
            }

            return false;
        }

        // DM rules: must be a participant AND must be within the same company.
        $isParticipant = $conversation
            ->participants()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            return false;
        }

        // Developer bypass may allow broader access (local/dev tooling).
        if ($user->isDeveloper()) {
            return true;
        }

        $other = $conversation
            ->users()
            ->where('users.id', '!=', $user->id)
            ->select(['users.id', 'users.company_id'])
            ->first();

        if (!$other) {
            return false;
        }

        if (!$user->company_id || !$other->company_id) {
            return false;
        }

        return (int) $user->company_id === (int) $other->company_id;
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (!$this->view($user, $conversation)) {
            return false;
        }

        if ($conversation->type === Conversation::TYPE_CHANNEL) {
            if ($conversation->slug === 'announcements') {
                return $user->isAdmin() || $user->isHR();
            }

            if ($conversation->slug === 'hr-team') {
                return $user->isAdmin() || $user->isHR();
            }

            return false;
        }

        // DM rules
        $other = $conversation
            ->users()
            ->where('users.id', '!=', $user->id)
            ->select(['users.id', 'users.role', 'users.company_id'])
            ->first();

        if (!$other) {
            return false;
        }

        // Enforce same-company messaging.
        if (!$user->isDeveloper()) {
            if (!$user->company_id || !$other->company_id) {
                return false;
            }

            if ((int) $user->company_id !== (int) $other->company_id) {
                return false;
            }
        }

        // Employees can DM only HR or Managers (MVP allows any Manager)
        if ($user->isEmployee()) {
            return in_array($other->role(), [User::ROLE_HR, User::ROLE_MANAGER], true);
        }

        // Admin/HR/Manager can DM each other.
        if (in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true)) {
            if (in_array($other->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true)) {
                return true;
            }

            // Allow messaging employees
            return $other->role() === User::ROLE_EMPLOYEE;
        }

        return false;
    }
}
