<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR], true);
    }

    public function view(User $user, LeaveType $leaveType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, LeaveType $leaveType): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $this->viewAny($user);
    }
}
