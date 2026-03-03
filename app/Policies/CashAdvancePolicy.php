<?php

namespace App\Policies;

use App\Models\CashAdvance;
use App\Models\User;

class CashAdvancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true);
    }

    public function view(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR], true);
    }

    public function approve(User $user, CashAdvance $cashAdvance): bool
    {
        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR], true);
    }

    public function reject(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->approve($user, $cashAdvance);
    }

    public function addDeduction(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->approve($user, $cashAdvance);
    }

    public function downloadAttachment(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->view($user, $cashAdvance);
    }

    public function complete(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->approve($user, $cashAdvance);
    }
}
