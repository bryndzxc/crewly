<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * @return array{base:string,company_id:int}|null
     */
    private function companyChannelFromSlug(?string $slug): ?array
    {
        $s = trim((string) ($slug ?? ''));
        if ($s === '') {
            return null;
        }

        if (preg_match('/^(announcements|hr-team)-(\d+)$/', $s, $m) !== 1) {
            return null;
        }

        return [
            'base' => (string) $m[1],
            'company_id' => (int) $m[2],
        ];
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($conversation->type === Conversation::TYPE_CHANNEL) {
            $parsed = $this->companyChannelFromSlug($conversation->slug);
            if (!$parsed) {
                // Legacy/global channels are no longer accessible.
                return false;
            }

            if ((int) ($user->company_id ?? 0) < 1) {
                return false;
            }

            if ((int) $user->company_id !== (int) $parsed['company_id']) {
                return false;
            }

            if ($parsed['base'] === 'announcements') {
                return true;
            }

            if ($parsed['base'] === 'hr-team') {
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

        // Allow any participant to view DMs with developer/support accounts.
        if ($other->isDeveloper()) {
            return true;
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
            $parsed = $this->companyChannelFromSlug($conversation->slug);
            if (!$parsed) {
                return false;
            }

            // Announcements and HR Team are HR/Admin writable.
            if (in_array($parsed['base'], ['announcements', 'hr-team'], true)) {
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

        // Allow cross-company messaging only when DMing developer/support accounts.
        if (!$user->isDeveloper() && $other->isDeveloper()) {
            return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true);
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
